import {reducer} from '#/main/log/administration/logs/store'
import {LogsTool} from '#/main/log/administration/logs/containers/tool'

/**
 * Logs administration tool application.
 */
export default {
  component: LogsTool,
  store: reducer
}
