
* Class extensions
	* `XF\Template\Templater`
		* add `fnBanner` template function

* code event listeners
	* `app_setup`
		* define `banner` subcontainer
	* `templater_setup`
		* add `banner` template function

* Fragile points:

		
* Testing:
	* unit tests for banner generation using Flysystem memory adapter
	* ...
	