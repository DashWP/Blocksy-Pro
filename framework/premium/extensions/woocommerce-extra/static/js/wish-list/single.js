import {
	prepareListWithSimpleProduct,
	prepareListWithVariableItem,
} from './common'

export const maybeHandleFavoriteSingleProduct = (el, operation, variable) => {
	const entrySummary = el.closest('.entry-summary')
	let newList = []

	if (
		entrySummary.querySelector('[name="variation_id"]') &&
		entrySummary.querySelector('[name="variation_id"]').value &&
		parseFloat(entrySummary.querySelector('[name="variation_id"]').value) &&
		variable
	) {
		const variation_id = parseFloat(
			entrySummary.querySelector('[name="variation_id"]').value
		)

		newList = prepareListWithVariableItem(
			entrySummary,
			variation_id,
			operation
		)
	} else {
		const productId = parseFloat(el.dataset.id)

		newList = prepareListWithSimpleProduct(productId, operation)
	}

	return newList
}
