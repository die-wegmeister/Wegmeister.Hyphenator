# Wegmeister.Hyphenator

Neos-Plugion of the Word-Hyphenation for PHP based on the TeX-Hyphenation algorithm.


## Installation

To install the package simply run

```
composer require wegmeister/hyphenator
```

## Usage

Add the hyphenator to the elements you want hyphenation on:

Usage via fusion:

```fusion
prototype(Vendor.Package:Element) {
	# Add the hyphenator to a specific property only:
	property = ${q(node).property('property')}
	property.@process.hyphenate = Wegmeister.Hyphenator:Hyphenate {
		# Set locale to the locale you want to use, defaults to the current locale
		locale = 'de_DE'

		# Set forceConversion to "true" if you also want to hyphenate the text in the backend.
		forceConversion = true
	}

	# Instead of applying the hyphenator to all properties, you can hyphenate the complete element.
	# This will not hyphenate html tags:
	@process.hyphenateWholeElement = Wegmeister.Hyphenator:Hyphenate
}
```


Usage via fluid:

```fluid
{namespace hyphenator=Wegmeister\Hyphenator\ViewHelpers}

<!--
Parameters are optional:
- locale: Custom locale to use for hyphenation (default: locale of current language)
- force:  Force hyphenation in backend, too.
-->

<hyphenator:format.hyphenate locale="de_DE" force="{true}">
	Text and <span>HTML</span> to hyphenate.
</hyphenator:format.hyphenate>

OR

{text -> hyphenator:format.hyphenate(locale: 'de_DE', force: true)}
```
