import { createElement, useState } from '@wordpress/element'

import { __ } from 'ct-i18n'

const Preview = ({ attributes }) => {
	const { showTooltips, showResetButton, showPrices } = attributes

	return (
		<div className="ct-filter-widget-wrapper">
			<div className="ct-price-filter">
				<div className="ct-price-filter-slider">
					<div
						className="ct-price-filter-range-track"
						style={{
							'--start': `10%`,
							'--end': `70%`,
						}}></div>

					<span
						className="ct-price-filter-range-handle-min"
						style={{
							'inset-inline-start': `10%`,
						}}
					/>

					<span
						className="ct-price-filter-range-handle-max"
						style={{
							'inset-inline-start': `70%`,
						}}
					/>

					<input type="range" value={10} />
					<input type="range" value={100} />
				</div>

				{showPrices ? (
					<div className="ct-price-filter-inputs">
						<span>Price:&nbsp;</span>
						<span className="ct-price-filter-min">$10</span>
						<span>&nbsp;-&nbsp;</span>
						<span className="ct-price-filter-max">$70</span>
					</div>
				) : null}
			</div>

			{showResetButton ? (
				<div class="ct-filter-reset wp-block-button is-style-outline">
					<a
						href="#"
						class="ct-button-ghost wp-element-button wp-block-button__link">
						<svg
							width="12"
							height="12"
							viewBox="0 0 15 15"
							fill="currentColor">
							<path d="M8.5,7.5l4.5,4.5l-1,1L7.5,8.5L3,13l-1-1l4.5-4.5L2,3l1-1l4.5,4.5L12,2l1,1L8.5,7.5z"></path>
						</svg>
						{__('Reset Filter', 'blocksy-companion')}
					</a>
				</div>
			) : null}
		</div>
	)
}

export default Preview
