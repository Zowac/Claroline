import React, {Fragment} from 'react'
import get from 'lodash/get'

import {trans} from '#/main/app/intl/translation'
import {PropTypes as T, implementPropTypes} from '#/main/app/prop-types'
import {DataInput as DataInputTypes} from '#/main/app/data/types/prop-types'
import {FormGroup} from '#/main/app/content/form/components/group'

import {Workspace as WorkspaceTypes} from '#/main/core/workspace/prop-types'
import {WorkspaceInput} from '#/main/core/data/types/workspace/components/input'
import {NumberInput} from '#/main/app/data/types/number/components/input'

// todo : manages errors

const WorkspaceScoreAboveInput = (props) =>
  <Fragment>
    <FormGroup
      id={`${props.id}-workspace`}
      label={trans('workspace')}
    >
      <WorkspaceInput
        id={`${props.id}-workspace`}
        disabled={props.disabled}
        onChange={(value) => props.onChange({workspace: value})}
        value={get(props.value, 'workspace')}
        size={props.size}
      />
    </FormGroup>

    <FormGroup
      id={`${props.id}-score`}
      className="form-last"
      label={trans('score')}
      help={trans('score_above_help', {}, 'badge')}
    >
      <NumberInput
        id={`${props.id}-score`}
        disabled={props.disabled}
        onChange={(value) => props.onChange({value: value})}
        min={0}
        max={100}
        value={get(props.value, 'value')}
        size={props.size}
        unit="%"
      />
    </FormGroup>
  </Fragment>

implementPropTypes(WorkspaceScoreAboveInput, DataInputTypes, {
  // more precise value type
  value: T.shape({
    workspace: T.shape(
      WorkspaceTypes.propTypes
    ),
    value: T.number
  })
}, {
  value: {workspace: null, value: null}
})

export {
  WorkspaceScoreAboveInput
}
