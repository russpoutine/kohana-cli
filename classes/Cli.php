<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Cli class
 *
 * Interact with the command line by accepting input options, parameters and output text
 *
 * @package  Kohana
 * @category Classes
 * @author  Phil Sturgeon
 * @autor  Shane Pearson
 * @link  http://fuelphp.com/docs/classes/cli.html
 */
class Cli {

	public static $readline_support = false;

	public static $wait_msg = 'Press any key to continue...';

	protected static $_args = NULL;

	protected static $foreground_colors = array(
		'black'   => '0;30',
		'dark_gray'  => '1;30',
		'blue'   => '0;34',
		'light_blue' => '1;34',
		'green'   => '0;32',
		'light_green' => '1;32',
		'cyan'   => '0;36',
		'light_cyan' => '1;36',
		'red'   => '0;31',
		'light_red'  => '1;31',
		'purple'  => '0;35',
		'light_purple' => '1;35',
		'brown'   => '0;33',
		'yellow'  => '1;33',
		'light_gray' => '0;37',
		'white'   => '1;37',
	);

	protected static $background_colors = array(
		'black'   => '40',
		'red'   => '41',
		'green'   => '42',
		'yellow'  => '43',
		'blue'   => '44',
		'magenta'  => '45',
		'cyan'   => '46',
		'light_gray' => '47',
	);

	public static function _init()
	{
		if ( ! Kohana::$is_cli)
		{
			throw new Exception('Cli class cannot be used outside of the command line.');
		}

		if ( ! isset(self::$_args))
		{
			for ($i = 1; $i < $_SERVER['argc']; $i++)
			{
				$arg = explode('=', $_SERVER['argv'][$i]);

				self::$_args[$i] = $arg[0];

				if (count($arg) > 1 || strncmp($arg[0], '-', 1) === 0)
				{
					self::$_args[ltrim($arg[0], '-')] = isset($arg[1]) ? $arg[1] : true;
				}
			}
		}

		// Readline is an extension for PHP that makes interactive with PHP much more bash-like
		// http://www.php.net/manual/en/readline.installation.php
		self::$readline_support = extension_loaded('readline');
	}

	/**
	 * Returns the option with the given name. You can also give the option
	 * number.
	 *
	 * Named options must be in the following formats:
	 * php index.php user -v --v -name=John --name=John
	 *
	 * @param string|int $name the name of the option (int if unnamed)
	 * @return string
	 */
	public static function option($name, $default = null)
	{
		if ( ! isset(self::$_args[$name]))
		{
			return $default;
		}
		return self::$_args[$name];
	}

	/**
	 * Returns one or more command-line options. Options are specified using
	 * standard CLI syntax:
	 *
	 *     php index.php --username=john.smith --password=secret --var="some value with spaces"
	 *
	 *     // Get the values of "username" and "password"
	 *     $auth = CLI::options('username', 'password');
	 *
	 * @param   string  option name
	 * @param   ...
	 * @return  array
	 */
	public static function options()
	{
		// Get all of the requested options
		$options = func_get_args();

		// Found option values
		$values = array();

		// Skip the first option, it is always the file executed
		foreach (self::$_args as $opt => $value)
		{
			if (in_array($opt, $options))
			{
				// Set the given value
				$values[$opt] = $value;
			}
		}

		return $values;
	}

	/**
	 * Get input from the shell, using readline or the standard STDIN
	 *
	 * Named options must be in the following formats:
	 * php index.php user -v --v -name=John --name=John
	 *
	 * @param string|int $name the name of the option (int if unnamed)
	 * @return string
	 */
	public static function input($prefix = '')
	{
		if (self::$readline_support)
		{
			return readline($prefix);
		}

		echo $prefix;
		return fgets(STDIN);
	}


	/**
	 * Asks the user for input.  This can have either 1 or 2 arguments.
	 *
	 * Usage:
	 *
	 * // Waits for any key press
	 * self::cli->prompt();
	 *
	 * // Takes any input
	 * $color = self::cli->prompt('What is your favorite color?');
	 *
	 * // Takes any input, but offers default
	 * $color = self::cli->prompt('What is your favourite color?', 'white');
	 *
	 * // Will only accept the options in the array
	 * $ready = self::cli->prompt('Are you ready?', array('y','n'));
	 *
	 * @return string the user input
	 */
	public static function prompt()
	{
		$args = func_get_args();

		$options = array();
		$output = '';
		$default = null;

		// How many we got
		$arg_count = count($args);

		// Is the last argument a boolean? True means required
		$required = end($args) === true;

		// Reduce the argument count if required was passed, we don't care about that anymore
		$required === true and --$arg_count;

		// This method can take a few crazy combinations of arguments, so lets work it out
		switch ($arg_count)
		{
		case 2:

			// E.g: $ready = self::cli->prompt('Are you ready?', array('y','n'));
			if (is_array($args[1]))
			{
				list($output, $options)=$args;
			}

			// E.g: $color = self::cli->prompt('What is your favourite color?', 'white');
			elseif (is_string($args[1]))
			{
				list($output, $default)=$args;
			}

			break;

		case 1:

			// No question (probably been asked already) so just show options
			// E.g: $ready = self::cli->prompt(array('y','n'));
			if (is_array($args[0]))
			{
				$options = $args[0];
			}

			// Question without options
			// E.g: $ready = self::cli->prompt('What did you do today?');
			elseif (is_string($args[0]))
			{
				$output = $args[0];
			}

			break;
		}

		// If a question has been asked with the read
		if ($output !== '')
		{
			$extra_output = '';

			if ($default !== null)
			{
				$extra_output = ' [ Default: "'.$default.'" ]';
			}

			elseif ($options !== array())
			{
				$extra_output = ' [ '.implode(', ', $options).' ]';
			}

			fwrite(STDOUT, $output.$extra_output.': ');
		}

		// Read the input from keyboard.
		($input = trim(self::input())) or $input = $default;

		// No input provided and we require one (default will stop this being called)
		if (empty($input) and $required === true)
		{
			self::write('This is required.');
			self::new_line();

			$input = call_user_func(array($this, 'prompt'), $args);
		}

		// If options are provided and the choice is not in the array, tell them to try again
		if ( ! empty($options) && ! in_array($input, $options))
		{
			self::write('This is not a valid option. Please try again.');
			self::new_line();

			$input = call_user_func_array(array($this, 'prompt'), $args);
		}

		return $input;
	}

	/**
	 * Outputs a string to the cli.  If you send an array it will implode them
	 * with a line break.
	 *
	 * @param string|array $text the text to output, or array of lines
	 */
	public static function write($text = '', $foreground = null, $background = null)
	{
		if (is_array($text))
		{
			$text = implode(PHP_EOL, $text);
		}

		if ($foreground or $background)
		{
			$text = self::color($text, $foreground, $background);
		}

		fwrite(STDOUT, $text.PHP_EOL);
	}

	/**
	 * Outputs an error to the CLI using STDERR instead of STDOUT
	 *
	 * @param string|array $text the text to output, or array of errors
	 */
	public static function error($text = '', $foreground = 'light_red', $background = null)
	{
		if (is_array($text))
		{
			$text = implode(PHP_EOL, $text);
		}

		if ($foreground or $background)
		{
			$text = self::color($text, $foreground, $background);
		}

		fwrite(STDERR, $text.PHP_EOL);
	}

	/**
	 * Beeps a certain number of times.
	 *
	 * @param int $num the number of times to beep
	 */
	public static function beep($num = 1)
	{
		echo str_repeat("\x07", $num);
	}

	/**
	 * Waits a certain number of seconds, optionally showing a wait message and
	 * waiting for a key press.
	 *
	 * @param int  $seconds number of seconds
	 * @param bool $countdown show a countdown or not
	 */
	public static function wait($seconds = 0, $countdown = false)
	{
		if ($countdown === true)
		{
			$time = $seconds;

			while ($time > 0)
			{
				fwrite(STDOUT, $time.'... ');
				sleep(1);
				$time--;
			}
			self::write();
		}

		else
		{
			if ($seconds > 0)
			{
				sleep($seconds);
			}
			else
			{
				self::write(self::$wait_msg);
				self::read();
			}
		}
	}


	/**
	 * if oprerating system === windows
	 */
	public static function is_windows()
	{
		return 'win' === strtolower(substr(php_uname("s"), 0, 3));
	}

	/**
	 * Enter a number of empty lines
	 *
	 * @param integer Number of lines to output
	 * @return void
	 */
	function new_line($num = 1)
	{
		// Do it once or more, write with empty string gives us a new line
		for($i = 0; $i < $num; $i++)
		{
			self::write();
		}
	}

	/**
	 * Clears the screen of output
	 *
	 * @return void
	 */
	function clear_screen()
	{
		self::is_windows()

		// Windows is a bit crap at this, but their terminal is tiny so shove this in
		? self::new_line(40)

		// Anything with a flair of Unix will handle these magic characters
		: fwrite(STDOUT, chr(27)."[H".chr(27)."[2J");
	}

	/**
	 * Returns the given text with the correct color codes for a foreground and
	 * optionally a background color.
	 *
	 * @param string $text  the text to color
	 * @param atring $foreground the foreground color
	 * @param string $background the background color
	 * @return string the color coded string
	 */
	public static function color($text, $foreground, $background = null)
	{
		if (self::is_windows())
		{
			return $text;
		}

		if ( ! array_key_exists($foreground, self::$foreground_colors))
		{
			throw new Exception('Invalid CLI foreground color: '.$foreground);
		}

		if ( $background !== null and ! array_key_exists($background, self::$background_colors))
		{
			throw new Exception('Invalid CLI background color: '.$background);
		}

		$string = "\033[".self::$foreground_colors[$foreground]."m";

		if ($background !== null)
		{
			$string .= "\033[".self::$background_colors[$background]."m";
		}

		$string .= $text."\033[0m";

		return $string;
	}

	/**
	 * Spawn Background Process
	 *
	 * Launches a background process (note, provides no security itself, $call must be sanitised prior to use)
	 * @param string $call the system call to make
	 * @return void
	 * @author raccettura
	 * @link http://robert.accettura.com/blog/2006/09/14/asynchronous-processing-with-php/
	 */
	public static function spawn($call, $output = '/dev/null')
	{
		// Windows
		if(static::is_windows())
		{
			pclose(popen('start /b '.$call, 'r'));
		}

		// Some sort of UNIX
		else
		{
			pclose(popen($call.' > '.$output.' &', 'r'));
		}
	}

} // end class Cli