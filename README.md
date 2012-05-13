# Kohana-CLI

Interact with the command line by accepting input options, parameters and output text.

## History

This library was originally written by Phil Sturgeon in 2009 and was... alright. It was totally rewritten for CodeIgniter 2.x based on the FuelPHP CLI library.

## Requirements

* Kohana 3.x
* PHP 5.3.5+

## Examples

	// Output "Hello World" to the CLI
	Cli::write('Hello World!');

	// Waits for any key press
	Cli::prompt();

	// Takes any input
	$color = Cli::prompt('What is your favorite color?');

	// Takes any input, but offers default
	$color = Cli::prompt('What is your favorite color?', 'white');

	// Will only accept the options in the array
	$ready = Cli::prompt('Are you ready?', array('y','n'));

	Cli::write('Loading...');
	Cli::wait(5, true);