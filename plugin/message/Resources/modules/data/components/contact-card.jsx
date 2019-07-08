import React from 'react'
import {PropTypes as T} from 'prop-types'
import omit from 'lodash/omit'

import {UserCard} from '#/main/core/user/data/components/user-card'

const ContactCard = props =>
  <UserCard
    {...omit(props, 'data')}
    {...props.data}
  />

ContactCard.propTypes = {
  data: T.object.isRequired
}

export {
  ContactCard
}
