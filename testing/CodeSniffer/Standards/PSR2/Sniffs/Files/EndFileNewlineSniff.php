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
class PSR2_Sniffs_Files_EndFileNewlineSniff implements PHP_CodeSniffer_Sniff
{

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
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        // We are only interested if this is the first open tag and in a file
        // that only contains PHP code.
        if ($stackPtr !== 0) {
            if ($phpcsFile->findPrevious(array(T_OPEN_TAG, T_INLINE_HTML), ($stackPtr - 1)) !== false) {
                return;
            }
        }

        if ($phpcsFile->findNext(T_INLINE_HTML, ($stackPtr + 1)) !== false) {
            return;
        }

        // Skip to the end of the file.
        $tokens    = $phpcsFile->getTokens();
        $lastToken = ($phpcsFile->numTokens - 1);

        // Hard-coding the expected \n in this sniff as it is PSR-2 specific and
        // PSR-2 enforces the use of unix style newlines.
        if (substr($tokens[$lastToken]['content'], -1) !== "\n") {
            $error = 'Expected 1 newline at end of file; 0 found';
            $fix   = $phpcsFile->addFixableError($error, $lastToken, 'NoneFound');
            if ($fix === true && $phpcsFile->fixer->enabled === true) {
                $phpcsFile->fixer->addNewline($lastToken);
            }

            return;
        }

        // Go looking for the last non-empty line.
        $lastLine = $tokens[$lastToken]['line'];
        if ($tokens[$lastToken]['code'] === T_WHITESPACE) {
            $lastCode = $phpcsFile->findPrevious(T_WHITESPACE, ($lastToken - 1), null, true);
        } else {
            $lastCode = $lastToken;
        }

        $lastCodeLine = $tokens[$lastCode]['line'];
        $blankLines   = ($lastLine - $lastCodeLine);
        if ($blankLines > 0) {
            $error = 'Expected 1 blank line at end of file; %s found';
            $data  = array($blankLines + 1);
            $fix   = $phpcsFile->addFixableError($error, $lastCode, 'TooMany', $data);

            if ($fix === true && $phpcsFile->fixer->enabled === true) {
                $phpcsFile->fixer->beginChangeset();
                $phpcsFile->fixer->replaceToken($lastCode, rtrim($tokens[$lastCode]['content']));
                for ($i = ($lastCode + 1); $i < $lastToken; $i++) {
                    $phpcsFile->fixer->replaceToken($i, '');
                }

                $phpcsFile->fixer->replaceToken($lastToken, $phpcsFile->eolChar);
                $phpcsFile->fixer->endChangeset();
            }
        }//end if

    }//end process()


}//end class
