import {url} from '#/main/app/api'
import {hasPermission} from '#/main/app/security'
import {trans} from '#/main/app/intl/translation'
import {URL_BUTTON} from '#/main/app/buttons'

export default (users) => ({
  type: URL_BUTTON,
  icon: 'fa fa-fw fa-mask',
  label: trans('show_as'),
  scope: ['object'],
  displayed: hasPermission('administrate', users[0]),
  target: url(['claro_index', {_switch: users[0].username}])+'#/desktop'
})
