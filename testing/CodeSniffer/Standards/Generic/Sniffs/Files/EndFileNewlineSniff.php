<?php
/**
 * Generic_Sniffs_Files_EndFileNewlineSniff.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2012 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Generic_Sniffs_Files_EndFileNewlineSniff.
 *
 * Ensures the file ends with a newline character.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2012 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: 2.0.0a1
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Generic_Sniffs_Files_EndFileNewlineSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = array(
                                   'PHP',
                                   'JS',
                                   'CSS',
                                  );


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_OPEN_TAG);

    }//end register()


    /**
     * Processes this sniff, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in
     *                                        the stack passed in $tokens.
     *
     * @return int
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        // Skip to the end of the file.
        $tokens   = $phpcsFile->getTokens();
        $stackPtr = ($phpcsFile->numTokens - 1);

        if ($phpcsFile->tokenizerType !== 'PHP') {
            $stackPtr--;
        }

        $eolCharLen = strlen($phpcsFile->eolChar);
        $lastChars  = substr($tokens[$stackPtr]['content'], ($eolCharLen * -1));
        if ($lastChars !== $phpcsFile->eolChar) {
            $error = 'File must end with a newline character';
            $phpcsFile->addError($error, $stackPtr, 'NotFound');
        }

        // Ignore the rest of the file.
        return ($phpcsFile->numTokens + 1);

    }//end process()


}//end class
