import { createElement, useEffect } from '@wordpress/element'
import { __ } from 'ct-i18n'

import Checkbox from './Checkbox'
import Label from './Label'
import Counter from './Counter'

const AttributePreview = ({ blockData, maybeAttribute, attributes, item }) => {
	const maybeColor = item?.meta?.accent_color?.default?.color
	const maybeImage = item?.meta?.image?.url

	return (
		<li className="ct-filter-item">
			<div className="ct-filter-item-inner">
				<a href="#">
					<Checkbox
						showCheckbox={attributes.showAttributesCheckbox}
					/>

					{blockData.has_swatches && attributes.showItemsRendered ? (
						<>
							{(maybeAttribute?.type === 'color' &&
								maybeColor !== 'CT_CSS_SKIP_RULE') ||
							(maybeAttribute?.type === 'image' && maybeImage) ||
							maybeAttribute?.type === 'button' ? (
								<span className="ct-swatch-container">
									{maybeAttribute?.type === 'color' ? (
										<span
											className="ct-swatch"
											style={{
												backgroundColor: maybeColor,
											}}
										/>
									) : null}
									{maybeAttribute?.type === 'image' ? (
										<span className="ct-media-container ct-swatch">
											<img
												src={maybeImage}
												alt={item.name}
											/>
										</span>
									) : null}
									{maybeAttribute?.type === 'button' ? (
										<span className="ct-swatch">
											{item?.meta?.short_name ||
												item?.name}
										</span>
									) : null}
								</span>
							) : null}
						</>
					) : null}

					<Label label={item.name} showLabel={attributes.showLabel} />

					<Counter
						count={item.count}
						showCounters={attributes.showCounters}
					/>
				</a>
			</div>
		</li>
	)
}

export default AttributePreview
