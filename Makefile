# customization

PACKAGE_NAME = icybee/module-files
PACKAGE_VERSION = 4.0
COMPOSER_ENV = COMPOSER_ROOT_VERSION=$(PACKAGE_VERSION)

# do not edit the following lines

usage:
	@echo "test:  Runs the test suite.\ndoc:   Creates the documentation.\nclean: Removes the documentation, the dependencies and the Composer files."

vendor:
	@$(COMPOSER_ENV) composer install

update:
	@$(COMPOSER_ENV) composer update

autoload: vendor
	@$(COMPOSER_ENV) composer dump-autoload

test: vendor
	@rm -Rf tests/repository/files
	@rm -Rf tests/repository/files-index
	@rm -Rf tests/repository/tmp
	@rm  -f tests/sandbox/*-*
	@phpunit

test-coverage: vendor
	@mkdir -p build/coverage
	@phpunit --coverage-html ../build/coverage

doc: vendor
	@mkdir -p build/docs
	@apigen generate \
	--source lib \
	--destination build/docs/ \
	--title "$(PACKAGE_NAME) v$(PACKAGE_VERSION)" \
	--template-theme "bootstrap"

clean:
	@rm -fR build
	@rm -fR vendor
	@rm  -f composer.lock
	@rm -Rf tests/repository/files
	@rm -Rf tests/repository/files-index
	@rm -Rf tests/repository/tmp
	@rm  -f tests/sandbox/*-*
