import {connect} from 'react-redux'

import {withRouter} from '#/main/app/router'
import {withReducer} from '#/main/app/store/components/withReducer'

// the component to connect
import {ResourceMain as ResourceMainComponent} from '#/main/core/resource/components/main'
// the store to use
import {actions, reducer, selectors} from '#/main/core/resource/store'

const ResourceMain = withRouter(
  withReducer(selectors.STORE_NAME, reducer)(
    connect(
      (state) => ({
        path: selectors.path(state),
        resourceType: selectors.resourceType(state),
        loaded: selectors.loaded(state)
      }),
      (dispatch) => ({
        loadNode(resourceId) {
          return dispatch(actions.fetchNode(resourceId))
        }
      })
    )(ResourceMainComponent)
  )
)

export {
  ResourceMain
}
