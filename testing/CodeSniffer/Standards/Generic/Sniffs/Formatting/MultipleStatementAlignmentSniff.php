<?php
/**
 * Generic_Sniffs_Formatting_MultipleStatementAlignmentSniff.
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
 * Generic_Sniffs_Formatting_MultipleStatementAlignmentSniff.
 *
 * Checks alignment of assignments. If there are multiple adjacent assignments,
 * it will check that the equals signs of each assignment are aligned. It will
 * display a warning to advise that the signs should be aligned.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2012 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: 2.0.0a1
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Generic_Sniffs_Formatting_MultipleStatementAlignmentSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = array(
                                   'PHP',
                                   'JS',
                                  );

    /**
     * If true, an error will be thrown; otherwise a warning.
     *
     * @var bool
     */
    public $error = false;

    /**
     * The maximum amount of padding before the alignment is ignored.
     *
     * If the amount of padding required to align this assignment with the
     * surrounding assignments exceeds this number, the assignment will be
     * ignored and no errors or warnings will be thrown.
     *
     * @var int
     */
    public $maxPadding = 1000;


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return PHP_CodeSniffer_Tokens::$assignmentTokens;

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return int
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Ignore assignments used in a condition, like an IF or FOR.
        if (isset($tokens[$stackPtr]['nested_parenthesis']) === true) {
            foreach ($tokens[$stackPtr]['nested_parenthesis'] as $start => $end) {
                if (isset($tokens[$start]['parenthesis_owner']) === true) {
                    return;
                }
            }
        }

        $lastAssign = $this->checkAlignment($phpcsFile, $stackPtr);
        return ($lastAssign + 1);

    }//end process()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return int
     */
    public function checkAlignment(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $assignments = array();
        $prevAssign  = null;
        $lastLine    = $tokens[$stackPtr]['line'];
        $maxPadding  = null;
        $stopped     = null;
        $lastCode    = $stackPtr;
        $lastSemi    = null;

        $find = array_diff(PHP_CodeSniffer_Tokens::$assignmentTokens, array(T_DOUBLE_ARROW));
        for ($assign = $stackPtr; $assign < $phpcsFile->numTokens; $assign++) {
            if (in_array($tokens[$assign]['code'], $find) === false) {
                // A blank line indicates that the assignment block has ended.
                if (in_array($tokens[$assign]['code'], PHP_CodeSniffer_tokens::$emptyTokens) === false) {
                    if (($tokens[$assign]['line'] - $tokens[$lastCode]['line']) > 1) {
                        break;
                    }

                    $lastCode = $assign;

                    if ($tokens[$assign]['code'] === T_SEMICOLON) {
                        if ($tokens[$assign]['conditions'] === $tokens[$stackPtr]['conditions']) {
                            if ($lastSemi !== null && $prevAssign !== null && $lastSemi > $prevAssign) {
                                // This statement did not have an assignment operator in it.
                                break;
                            } else {
                                $lastSemi = $assign;
                            }
                        } else {
                            // Statement is in a different context, so the block is over.
                            break;
                        }
                    }
                }//end if

                continue;
            }//end if

            if ($assign !== $stackPtr) {
                // Has to be nested inside the same conditions as the first assignment.
                if ($tokens[$assign]['conditions'] !== $tokens[$stackPtr]['conditions']) {
                    break;
                }

                // The assignment's end token must be on the line directly
                // below the current one to be in the same assignment block.
                $lineEnd = $phpcsFile->findNext(T_SEMICOLON, ($assign + 1));

                // And the end token must actually belong to this assignment.
                $nextOpener = $phpcsFile->findNext(
                    PHP_CodeSniffer_Tokens::$scopeOpeners,
                    ($assign + 1)
                );

                if ($nextOpener !== false && $nextOpener < $lineEnd) {
                    break;
                }

                // Make sure it is not assigned inside a condition (eg. IF, FOR).
                if (isset($tokens[$assign]['nested_parenthesis']) === true) {
                    foreach ($tokens[$assign]['nested_parenthesis'] as $start => $end) {
                        if (isset($tokens[$start]['parenthesis_owner']) === true) {
                            break(2);
                        }
                    }
                }
            }//end if

            $var = $phpcsFile->findPrevious(
                PHP_CodeSniffer_Tokens::$emptyTokens,
                ($assign - 1),
                null,
                true
            );

            // Make sure we wouldn't break our max padding length if we
            // aligned with this statement, or they wouldn't break the max
            // padding length if they aligned with us.
            $varEnd    = $tokens[($var + 1)]['column'];
            $assignLen = strlen($tokens[$assign]['content']);
            if ($assign !== $stackPtr) {
                if (($varEnd + 1) > $assignments[$prevAssign]['assign_col']) {
                    $padding = ($varEnd - $assignments[$prevAssign]['var_end'] + $assignLen - $assignments[$prevAssign]['assign_len'] + 1);
                    if ($padding > $this->maxPadding) {
                        $stopped = $assign;
                        break;
                    }

                    $assignColumn = ($assignments[$prevAssign]['var_end'] + $padding);
                } else {
                    $padding = ($assignments[$prevAssign]['assign_col'] - $varEnd + $assignments[$prevAssign]['assign_len'] - $assignLen);
                    if ($padding === 0) {
                        $padding = 1;
                    }

                    if ($padding > $this->maxPadding) {
                        $stopped = $assign;
                        break;
                    }

                    $assignColumn = ($varEnd + $padding);
                }//end if

                if (($assignColumn + $assignLen) > ($assignments[$maxPadding]['assign_col'] + $assignments[$maxPadding]['assign_len'])) {
                    $newPadding = ($varEnd - $assignments[$maxPadding]['var_end'] + $assignLen - $assignments[$maxPadding]['assign_len'] + 1);
                    if ($newPadding > $this->maxPadding) {
                        $stopped = $assign;
                        break;
                    } else {
                        // New alignment settings for previous assignments.
                        foreach ($assignments as $i => $data) {
                            if ($i === $assign) {
                                break;
                            }

                            $newPadding = ($varEnd - $data['var_end'] + $assignLen - $data['assign_len'] + 1);
                            $assignments[$i]['expected']   = $newPadding;
                            $assignments[$i]['assign_col'] = ($data['var_end'] + $newPadding);
                        }

                        $padding      = 1;
                        $assignColumn = ($varEnd + 1);
                    }
                } else if ($padding > $assignments[$maxPadding]['expected']) {
                    $maxPadding = $assign;
                }//end if
            } else {
                $padding      = 1;
                $assignColumn = ($varEnd + 1);
                $maxPadding   = $assign;
            }//end if

            $found = 0;
            if ($tokens[($var + 1)]['code'] === T_WHITESPACE) {
                $found = strlen($tokens[($var + 1)]['content']);
            }

            $assignments[$assign] = array(
                                     'var_end'    => $varEnd,
                                     'assign_len' => $assignLen,
                                     'assign_col' => $assignColumn,
                                     'expected'   => $padding,
                                     'found'      => $found,
                                    );

            $lastLine   = $tokens[$assign]['line'];
            $prevAssign = $assign;
        }//end for

        foreach ($assignments as $assignment => $data) {
            if ($data['found'] === $data['expected']) {
                continue;
            }

            $expectedText  = $data['expected'];
            $expectedText .= ($data['expected'] === 1) ? ' space' : ' spaces';

            $foundText = $data['found'];
            if ($foundText === null) {
                $foundText = 'a new line';
            } else {
                $foundText .= ($foundText === 1) ? ' space' : ' spaces';
            }

            if (count($assignments) === 1) {
                $type  = 'Incorrect';
                $error = 'Equals sign not aligned correctly; expected %s but found %s';
            } else {
                $type  = 'NotSame';
                $error = 'Equals sign not aligned with surrounding assignments; expected %s but found %s';
            }

            $errorData = array(
                          $expectedText,
                          $foundText,
                         );

            if ($this->error === true) {
                $fix = $phpcsFile->addFixableError($error, $assignment, $type, $errorData);
            } else {
                $fix = $phpcsFile->addFixableWarning($error, $assignment, $type.'Warning', $errorData);
            }

            if ($fix === true && $phpcsFile->fixer->enabled === true && $data['found'] !== null) {
                $newContent = str_repeat(' ', $data['expected']);
                if ($data['found'] === 0) {
                    $phpcsFile->fixer->addContentBefore($assignment, $newContent);
                } else {
                    $phpcsFile->fixer->replaceToken(($assignment - 1), $newContent);
                }
            }
        }//end foreach

        if ($stopped !== null) {
            return $this->checkAlignment($phpcsFile, $stopped);
        } else {
            return $assignment;
        }

    }//end checkAlignment()


}//end class