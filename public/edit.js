window.addEvent('load', function() {

	var form = document.body.getElement('.form-primary.edit')
	, controls = []
	, controlValue = []

	form.getElements('input[type=text]').each(function (control) {

		controls.push(control)

		if (control.value)
		{
			controlValue[control.uniqueNumber] = true
		}
	})

	form.getElements('.widget-file').each(function(el) {

		var widget = Brickrouge.from(el)

		widget.addEvent('change', function(response) {

			Object.each(response.rc, function(value, key) {

				var control = document.id(form.elements[key])

				if (!control) return

				if (control.value && (controls.indexOf(control) == -1 || (controlValue[control.uniqueNumber] && controlValue[control.uniqueNumber] != control.value)))
				{
					return
				}

				controlValue[control.uniqueNumber] = value
				control.value = value
			})
		})
	})
})
