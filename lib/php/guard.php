<?php
//----------------------------------------------------------------------
// Copyright (c) 2012 Raytheon BBN Technologies
//
// Permission is hereby granted, free of charge, to any person obtaining
// a copy of this software and/or hardware specification (the "Work") to
// deal in the Work without restriction, including without limitation the
// rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Work, and to permit persons to whom the Work
// is furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be
// included in all copies or substantial portions of the Work.
//
// THE WORK IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
// OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
// MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
// NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
// HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE WORK OR THE USE OR OTHER DEALINGS
// IN THE WORK.
//----------------------------------------------------------------------

interface Guard {
  /**
   * Evaluate the guard. The guard is intended to be a closure over a
   * GeniMessage so no parameters are given.
   *
   * @return TRUE if the action is authorized, FALSE otherwise.
   */
  public function evaluate();
}

/**
 * A guard that always returns TRUE.
 */
class TrueGuard implements Guard
{
  public function evaluate()
  {
    return TRUE;
  }
}

/**
 * A guard that always returns FALSE.
 */
class FalseGuard implements Guard
{
  public function evaluate()
  {
    return FALSE;
  }
}

interface GuardFactory
{
  /**
   * Create authorization guards for the given message.
   *
   * @param message a GeniMessage
   * @return an (possibly empty) array of Guards
   */
  public function createGuards($message);
}


?>