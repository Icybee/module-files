!function (Brickrouge) {

	"use strict"

	/**
	 * @param {Brickrouge.WidgetEvent} ev
	 */
	Brickrouge.observeRunning(ev => {

		const form = document.body.querySelector('.form-primary.edit')
		const controls = []
		const controlValue = []

		form.querySelectorAll('input[type=text]').forEach(control => {

			controls.push(control)

			if (control.value)
			{
				controlValue[Brickrouge.uidOf(control)] = true
			}
		})

		form.querySelectorAll('.widget-file').forEach(element => {

			/**
			 * @param {Brickrouge.File.ChangeEvent} ev
			 */
			Brickrouge.from(element).observeChange(ev => {

				Object.forEach(ev.response.rc, (value, key) => {

					const control = form.elements[key]

					if (!control)
					{
						return
					}

					const uid = Brickrouge.uidOf(control)

					if (control.value && (controls.indexOf(control) == -1 || (controlValue[uid] && controlValue[uid] != control.value)))
					{
						return
					}

					controlValue[uid] = value
					control.value = value
				})
			})
		})

	})

} (Brickrouge)
