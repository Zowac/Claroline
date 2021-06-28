<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\WebResourceBundle;

use Claroline\KernelBundle\Bundle\DistributionPluginBundle;
use Claroline\WebResourceBundle\Installation\AdditionalInstaller;

class ClarolineWebResourceBundle extends DistributionPluginBundle
{
    public function hasMigrations(): bool
    {
        return false;
    }

    public function getAdditionalInstaller()
    {
        return new AdditionalInstaller();
    }
}
