# customization

MODULE_NAME = "Icybee/Modules/Files"

# do not edit the following lines

usage:
	@echo "test:  Runs the test suite.\ndoc:   Creates the documentation.\nclean: Removes the documentation, the dependencies and the Composer files."

vendor:
	@composer install --prefer-source --dev

update:
	@composer update --prefer-source --dev

autoload: vendor
	@composer dump-autoload

test: vendor
	@phpunit

doc: vendor
	@mkdir -p "docs"

	@apigen \
	--source ./ \
	--destination docs/ --title $(MODULE_NAME) \
	--exclude "*/tests/*" \
	--exclude "*/composer/*" \
	--template-config /usr/share/php/data/ApiGen/templates/bootstrap/config.neon

clean:
	@rm -fR docs
	@rm -fR vendor
	@rm -f composer.lock
	@rm -f composer.phar
	@rm -Rf tests/repository/files
	@rm -Rf tests/repository/tmp
