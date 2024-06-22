import { createElement, useMemo } from '@wordpress/element'

import { useSelect } from '@wordpress/data'
import Checkbox from './Checkbox'
import Label from './Label'
import Counter from './Counter'

const HierarchicalItem = ({ taxItem, attributes }) => {
	const children = taxItem.children || []

	const showTaxLabels =
		(attributes.taxonomy === 'product_brands' && attributes.showLabel) ||
		attributes.taxonomy !== 'product_brands'

	const defaultExpanded = attributes.expandable
		? attributes.defaultExpanded
		: true

	const hierarchical =
		attributes.viewType === 'list' ? attributes.hierarchical : false

	const expandable =
		attributes.viewType === 'list' ? attributes.expandable : false

	return (
		<li className="ct-filter-item">
			<div className="ct-filter-item-inner">
				<a href="#">
					<Checkbox showCheckbox={attributes.showCheckbox} />

					{attributes.taxonomy === 'product_brands' &&
					taxItem?.logo?.url &&
					attributes.showItemsRendered ? (
						<div className="ct-product-brand">
							<div className="ct-media-container">
								<img
									src={taxItem.logo.url}
									alt={taxItem.name}
								/>
							</div>
						</div>
					) : null}

					<Label
						showLabel={showTaxLabels}
						label={taxItem.name}
						count={taxItem.count}
						withCount={
							attributes.showCounters &&
							expandable &&
							hierarchical
						}
					/>

					<Counter
						count={taxItem.count}
						showCounters={
							attributes.showCounters &&
							(!expandable || !hierarchical)
						}
					/>
				</a>
				{children.length && hierarchical && expandable ? (
					<span className="ct-expandable-trigger">
						<svg
							class="ct-icon"
							width="10"
							height="10"
							viewBox="0 0 25 25">
							<path d="M.207 17.829 12.511 5.525l1.768 1.768L1.975 19.596z"></path>
							<path d="m10.721 7.243 1.768-1.768L24.793 17.78l-1.768 1.767z"></path>
						</svg>
					</span>
				) : null}
			</div>

			{children.length && hierarchical && defaultExpanded ? (
				<ul className="ct-filter-children">
					{children.map((item) => {
						if (
							attributes.taxonomy_not_in.includes(item.term_id) &&
							attributes.excludeTaxonomy
						) {
							return null
						}

						return (
							<HierarchicalItem
								key={item.term_id}
								taxItem={item}
								attributes={attributes}
							/>
						)
					})}
				</ul>
			) : null}
		</li>
	)
}

export default HierarchicalItem
