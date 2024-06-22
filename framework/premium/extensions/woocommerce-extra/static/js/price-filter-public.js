import { registerDynamicChunk } from 'blocksy-frontend'
import { fetchDataFor } from './ajax-filter-public'
import { sprintf } from '@wordpress/i18n/build/sprintf'

const formatPrice = (price) => {
	const { priceFormat, currency } =
		ct_localizations.blocksy_woo_extra_price_filters

	return sprintf(priceFormat, currency, price)
}

const updateMinThumb = (parent, pos, tooltipMessage) => {
	const el = parent.querySelector('.ct-price-filter-range-handle-min')

	const tooltip = el.querySelector('.ct-tooltip')

	if (tooltip) {
		tooltip.innerText = tooltipMessage
	}

	el.style.insetInlineStart = pos + '%'
}

const updateMaxThumb = (parent, pos, tooltipMessage) => {
	const el = parent.querySelector('.ct-price-filter-range-handle-max')

	const tooltip = el.querySelector('.ct-tooltip')

	if (tooltip) {
		tooltip.innerText = tooltipMessage
	}

	el.style.insetInlineStart = pos + '%'
}

const updateRangeTrack = (parent, minPos, maxPos) => {
	const rangeTrack = parent.querySelector('.ct-price-filter-range-track')
	rangeTrack.style.setProperty('--start', minPos + '%')
	rangeTrack.style.setProperty('--end', maxPos + '%')
}

const handleDrag = (el, event, { parent, minEl, maxEl }) => {
	const minInput = parent.querySelector('.ct-price-filter-min')
	const maxInput = parent.querySelector('.ct-price-filter-max')

	const min = parseFloat(el.min)
	const max = parseFloat(el.max)

	const value = parseFloat(event.target.value)

	const minVal = Math.min(value, parseFloat(minEl.value))
	const maxVal = Math.max(value, parseFloat(maxEl.value))

	const minPos = Math.max(((minVal - min) / (max - min)) * 100, 0)
	const maxPos = Math.min(((maxVal - min) / (max - min)) * 100, 100)

	updateRangeTrack(parent, minPos, maxPos)

	if (minInput && maxInput) {
		minInput.innerText = formatPrice(minVal)
		maxInput.innerText = formatPrice(maxVal)
	}

	updateMinThumb(parent, minPos, formatPrice(minVal))
	updateMaxThumb(parent, maxPos, formatPrice(maxVal))

	minEl.value = minVal
	maxEl.value = maxVal

	if (el.name === 'min_price' && minVal >= maxVal) {
		maxEl.value = Math.min(minVal + 1, max)

		if (maxInput) {
			maxInput.innerText = formatPrice(Math.min(minVal + 1, max))
		}
	}

	if (el.name === 'max_price' && maxVal <= minVal) {
		minEl.value = Math.max(maxVal - 1, min)

		if (minInput) {
			minInput.innerText = formatPrice(Math.max(maxVal - 1, min))
		}
	}
}

const handleMount = (el, { event }) => {
	const isAjax = document.querySelector('[data-ajax-filters*="yes"]')
	const parent = el.closest('.ct-price-filter')
	const minEl = parent.querySelector('[type="range"][name="min_price"]')
	const maxEl = parent.querySelector('[type="range"][name="max_price"]')

	if (event.type === 'input') {
		if (el.type === 'range') {
			handleDrag(el, event, { parent, minEl, maxEl })
		}
		return
	}

	if (event.type === 'change') {
		const searchParams = new URLSearchParams(window.location.search)
		searchParams.set(minEl.name, minEl.value)
		searchParams.set(maxEl.name, maxEl.value)

		if (el.name === 'min_price' && el.value === el.min) {
			searchParams.delete(el.name)
		}

		if (el.name === 'max_price' && el.value === el.max) {
			searchParams.delete(el.name)
		}

		const requestUrl = `${window.location.pathname}${
			searchParams.toString().length ? `?${searchParams.toString()}` : ''
		}`
		if (!isAjax) {
			window.location.href = requestUrl
		} else {
			fetchDataFor(requestUrl)
			window.history.pushState({}, document.title, requestUrl)
		}
	}
}

registerDynamicChunk('blocksy_ext_woo_extra_price_filters', {
	mount: handleMount,
})

window.addEventListener(
	'popstate',
	function (event) {
		if (event.state) {
			fetchDataFor(window.location.href)
		}
	},
	false
)
