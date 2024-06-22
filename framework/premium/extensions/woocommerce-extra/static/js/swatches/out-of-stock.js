import cachedFetch from './cached-fetch'

const cleanupId = (id) => {
	if (id.includes('--')) {
		return id.split('--')[0]
	}

	return id
}

// product_id => {
//    Strategy #1:
//
//    attributes_stock: {
//      attribute_name: {
//        valid: [],
//        invalid: []
//      }
//    }
//    out_of_stock_variations: [],
//
//    ==========
//
//    Strategy #2:
//
//    all_variations: []
// }
const variations = {}

const getRelevantVariations = async (form, args = {}) => {
	args = {
		selectedAttributes: {},
		form: null,
		attribute_name: '',

		...args,
	}

	let productId = form.closest('[class*="post-"]')

	if (productId) {
		productId = [...productId.classList].find((c) => c.match(/^post-/))

		if (productId) {
			productId = parseFloat(productId.split('-')[1])
		}
	}

	// Can't compute product ID for some reason, returning empty array.
	if (!productId) {
		return []
	}

	if (variations[productId]) {
		return variations[productId]
	}

	// Product has a lot of variations, starting to load them via AJAX
	if (form.dataset.product_variations === 'false') {
		const result = await cachedFetch(
			ct_localizations.ajax_url +
				'?action=blocksy_swatches_get_product_out_of_stock_variations',
			{
				product_id: productId,
			}
		)

		variations[productId] = {
			out_of_stock_variations: result.data.variations,
			attributes_stock: result.data.attributes_stock,
		}

		return variations[productId]
	}

	// All variations are available. Just populating the cache to skip
	// further JSON parsing calls.
	const allVariations = JSON.parse(form.dataset.product_variations)

	variations[productId] = {
		all_variations: allVariations,
	}

	return variations[productId]
}

const markAsOutOfStock = (swatch) => {
	if (swatch.classList.contains('ct-out-of-stock')) {
		return
	}

	let outOfStockLabel = ''

	if (
		wc_add_to_cart_variation_params &&
		wc_add_to_cart_variation_params.i18n_out_of_stock
	) {
		outOfStockLabel = wc_add_to_cart_variation_params.i18n_out_of_stock
	}

	swatch.classList.add('ct-out-of-stock')

	const maybeTooltip = swatch.querySelector('.ct-tooltip')

	if (maybeTooltip && !maybeTooltip.textContent.includes(outOfStockLabel)) {
		maybeTooltip.textContent = `${maybeTooltip.textContent} - ${outOfStockLabel}`
	}
}

const markAsInStock = (swatch) => {
	if (!swatch.classList.contains('ct-out-of-stock')) {
		return
	}

	let outOfStockLabel = ''

	if (
		wc_add_to_cart_variation_params &&
		wc_add_to_cart_variation_params.i18n_out_of_stock
	) {
		outOfStockLabel = wc_add_to_cart_variation_params.i18n_out_of_stock
	}

	swatch.classList.remove('ct-out-of-stock')

	const maybeTooltip = swatch.querySelector('.ct-tooltip')

	if (maybeTooltip && !maybeTooltip.querySelector('.ct-media-container')) {
		maybeTooltip.textContent = maybeTooltip.textContent.replace(
			` - ${outOfStockLabel}`,
			''
		)
	}
}

const applyOutOfStockForSwatch = async (swatch, args = {}) => {
	args = {
		selectedAttributes: {},
		form: null,
		attribute_name: '',

		...args,
	}

	let isOutOfStock = false

	const variationsDescriptor = await getRelevantVariations(args.form, {
		attribute_name: args.attribute_name,

		// By not using transformedSelectedAttributes to make the request,
		// we will fill the cache much faster with all the variations
		// and we will avoid lots of small requests.
		selectedAttributes: args.selectedAttributes,
	})

	const transformedSelectedAttributes = {
		...args.selectedAttributes,
		[args.attribute_name]: swatch.dataset.value,
	}

	// Strategy #1
	// Usually, this is the case when the product has a lot of variations.
	if (variationsDescriptor.attributes_stock) {
		let isSwatchAlwaysOutOfStock = false

		const attributesStock =
			variationsDescriptor.attributes_stock[args.attribute_name]

		if (
			attributesStock &&
			attributesStock.invalid &&
			attributesStock.invalid.includes(swatch.dataset.value)
		) {
			isSwatchAlwaysOutOfStock = true
			isOutOfStock = true
		}

		if (!isSwatchAlwaysOutOfStock) {
			const maybeOutOfStockVariation =
				variationsDescriptor.out_of_stock_variations.find(
					(variation) => {
						if (!variation.attributes) {
							return false
						}

						return Object.keys(variation.attributes).every(
							(key) => {
								return (
									variation.attributes[key] ===
										transformedSelectedAttributes[key] ||
									variation.attributes[key] === ''
								)
							}
						)
					}
				)

			if (maybeOutOfStockVariation) {
				isOutOfStock = true
			}
		}
	}

	// Strategy #2
	//
	// If all variations are available, check is simpler.
	if (variationsDescriptor.all_variations) {
		const allVariations = variationsDescriptor.all_variations

		const maybeFoundVariation = allVariations.find((variation) => {
			if (!variation.attributes) {
				return false
			}

			return Object.keys(variation.attributes).every((key) => {
				return (
					variation.attributes[key] ===
						transformedSelectedAttributes[key] ||
					variation.attributes[key] === ''
				)
			})
		})

		if (maybeFoundVariation && !maybeFoundVariation.is_in_stock) {
			isOutOfStock = true
		}
	}

	if (isOutOfStock) {
		markAsOutOfStock(swatch)
		return
	}

	markAsInStock(swatch)
}

export const handleOutofStock = (el) => {
	const form = el.closest('[data-product_variations]')

	if (!form) {
		return
	}

	const selectsWithAttributes = Array.from(
		form.querySelectorAll('select')
	).filter((s) => s.closest('.ct-variation-swatches'))

	const selectedAttributes = selectsWithAttributes
		.filter((s) => s.value)
		.reduce((acc, s) => {
			acc[cleanupId(s.dataset.attribute_name)] = s.value

			return acc
		}, {})

	selectsWithAttributes.forEach((select) => {
		;[
			...select
				.closest('.ct-variation-swatches')
				.querySelectorAll('[data-value]'),
		].map((swatch) =>
			applyOutOfStockForSwatch(swatch, {
				form,
				selectedAttributes,
				attribute_name: cleanupId(select.dataset.attribute_name),
			})
		)
	})
}
