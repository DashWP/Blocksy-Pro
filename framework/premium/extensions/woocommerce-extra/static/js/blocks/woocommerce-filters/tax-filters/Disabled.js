import { createElement } from '@wordpress/element'

import { __ } from 'ct-i18n'

const errors = {
	categories: __('Please select a valid taxonomy.', 'blocksy-companion'),
	attributes: __('Please select a valid attribute.', 'blocksy-companion'),
}

const Disabled = ({ isError, type, children }) => {
	if (!isError) {
		return children
	}

	return (
		<div className="ct-block-notice components-notice is-warning">
			<div className="components-notice__content">
				<p>{errors?.[type]}</p>
			</div>
		</div>
	)
}

export default Disabled
