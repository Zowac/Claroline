<?php

namespace Claroline\CoreBundle\API\Crud\User;

use Claroline\AppBundle\API\Crud;
use Claroline\AppBundle\Event\Crud\CreateEvent;
use Claroline\AppBundle\Event\Crud\DeleteEvent;
use Claroline\AppBundle\Event\Crud\PatchEvent;
use Claroline\AuthenticationBundle\Security\Authentication\Authenticator;
use Claroline\CoreBundle\Entity\AbstractRoleSubject;
use Claroline\CoreBundle\Entity\Group;
use Claroline\CoreBundle\Entity\Role;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Library\Normalizer\TextNormalizer;
use Claroline\CoreBundle\Manager\RoleManager;
use Claroline\LogBundle\Messenger\Security\Message\AddRoleMessage;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RoleCrud
{
    /** @var Connection */
    private $conn;
    /** @var TokenStorageInterface */
    private $tokenStorage;
    /** @var Authenticator */
    private $authenticator;
    /** @var RoleManager */
    private $manager;
    /** @var MessageBusInterface */
    private $messageBus;
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        Connection $conn,
        TokenStorageInterface $tokenStorage,
        Authenticator $authenticator,
        RoleManager $manager,
        MessageBusInterface $messageBus,
        TranslatorInterface $translator
    ) {
        $this->conn = $conn;
        $this->tokenStorage = $tokenStorage;
        $this->authenticator = $authenticator;
        $this->manager = $manager;
        $this->messageBus = $messageBus;
        $this->translator = $translator;
    }

    public function preCreate(CreateEvent $event)
    {
        /** @var Role $role */
        $role = $event->getObject();

        if (empty($role->getName())) {
            switch ($role->getType()) {
                case Role::WS_ROLE:
                    if ($role->getWorkspace()) {
                        $role->setName(strtoupper('role_ws_'.TextNormalizer::toKey($role->getTranslationKey())).'_'.$role->getWorkspace()->getUuid());
                    }
                    break;
                case Role::USER_ROLE:
                    if (!empty($role->getUsers())) {
                        // user roles are only assigned to one user
                        $owner = $role->getUsers()[0];
                        $role->setName(strtoupper('role_user_'.strtoupper(TextNormalizer::toKey($owner->getUsername()))));
                    }
                    break;
                default:
                    $role->setName(strtoupper('role_'.TextNormalizer::toKey($role->getTranslationKey())));
            }
        }
    }

    public function postCreate(CreateEvent $event)
    {
        /** @var Role $role */
        $role = $event->getObject();

        if (Role::WS_ROLE === $role->getType() && $role->getWorkspace()) {
            // give open access to all the workspace resource
            $this->conn
                ->prepare("
                    INSERT INTO claro_resource_rights (role_id, mask, resourceNode_id)
                    SELECT {$role->getId()}, 1, resource.id FROM claro_resource_node resource
                    WHERE resource.workspace_id = {$role->getWorkspace()->getId()}
                ")
                ->execute();

            // init access rights for the workspace tools
            $this->conn
                ->prepare("
                    INSERT INTO claro_tool_rights (role_id, mask, ordered_tool_id)
                    SELECT {$role->getId()}, 0, ot.id 
                    FROM claro_ordered_tool AS ot
                    WHERE ot.workspace_id = {$role->getWorkspace()->getId()}
                ")
                ->execute();
        } elseif (Role::PLATFORM_ROLE === $role->getType()) {
            // init access rights for the desktop tools
            $this->conn
                ->prepare("
                    INSERT INTO claro_tool_rights (role_id, mask, ordered_tool_id)
                    SELECT {$role->getId()}, 0, ot.id 
                    FROM claro_ordered_tool AS ot
                    WHERE ot.workspace_id IS NULL AND user_id IS NULL
                ")
                ->execute();
        }
    }

    public function preDelete(DeleteEvent $event)
    {
        /** @var Role $role */
        $role = $event->getObject();

        if ($role->isReadOnly()) {
            // abort delete
            $event->block();
        }
    }

    public function prePatch(PatchEvent $event)
    {
        /** @var Role $role */
        $role = $event->getObject();

        // checks if we can add users/groups to the role
        if (Crud::COLLECTION_ADD === $event->getAction() && in_array($event->getProperty(), ['user', 'group'])) {
            /** @var AbstractRoleSubject $ars */
            $ars = $event->getValue();
            if ($ars->hasRole($role->getName()) || !$this->manager->validateRoleInsert($ars, $role)) {
                $event->block();
            }
        }
    }

    public function postPatch(PatchEvent $event)
    {
        // refresh token to get updated roles if this is the current user or if he is in the group
        if (in_array($event->getProperty(), ['user', 'group'])) {
            $currentUser = $this->tokenStorage->getToken()->getUser();

            $refresh = false;
            if ($event->getValue() instanceof User) {
                $refresh = $this->authenticator->isAuthenticatedUser($event->getValue());
            } elseif ($event->getValue() instanceof Group && $currentUser instanceof User) {
                $refresh = $currentUser->hasGroup($event->getValue());
            }

            if ($refresh) {
                $this->authenticator->createToken($currentUser);
            }

            // Dispatch event for User
            if ('add' === $event->getAction() && $event->getValue() instanceof User && $event->getObject() instanceof Role) {
                $this->messageBus->dispatch(
                    new AddRoleMessage(
                        $event->getValue()->getId(),
                        $this->tokenStorage->getToken()->getUser()->getId(),
                        'event.security.add_role',
                        $this->translator->trans('addRole', ['username' => $event->getValue()->getUsername(), 'role' => $event->getObject()->getName()], 'security')
                    )
                );
            } elseif ('remove' === $event->getAction() && $event->getValue() instanceof User && $event->getObject() instanceof Role) {
                $this->messageBus->dispatch(
                    new AddRoleMessage(
                        $event->getValue()->getId(),
                        $this->tokenStorage->getToken()->getUser()->getId(),
                        'event.security.remove_role',
                        $this->translator->trans('removeRole', ['username' => $event->getValue()->getUsername(), 'role' => $event->getObject()->getName()], 'security')
                    )
                );
            }

            // Dispatch event for Group
            if ('add' === $event->getAction() && $event->getValue() instanceof Group && $event->getObject() instanceof Role) {
                foreach ($event->getValue()->getUsers() as $user) {
                    $this->messageBus->dispatch(
                        new AddRoleMessage(
                            $user->getId(),
                            $this->tokenStorage->getToken()->getUser()->getId(),
                            'event.security.add_role',
                            $this->translator->trans('addRole', ['username' => $user->getUsername(), 'role' => $event->getObject()->getName()], 'security')
                        )
                    );
                }
            } elseif ('remove' === $event->getAction() && $event->getValue() instanceof Group && $event->getObject() instanceof Role) {
                foreach ($event->getValue()->getUsers() as $user) {
                    $this->messageBus->dispatch(
                        new AddRoleMessage(
                            $user->getId(),
                            $this->tokenStorage->getToken()->getUser()->getId(),
                            'event.security.remove_role',
                            $this->translator->trans('removeRole', ['username' => $user->getUsername(), 'role' => $event->getObject()->getName()], 'security')
                        )
                    );
                }
            }
        }
    }
}
