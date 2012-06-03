<?php
/* Copyright (c) 2012 kayson (kaysond) & Noah Manneschmidt (psoplayer)
 * https://github.com/noahm/MobileTribunal
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

////////////////////////////////////////////
//
//CONFIGURATION
//
////////////////////////////////////////////

////////////////////////////////////////////
//FORCE_SSL - 
//
//Default: True
//Description: Setting this to true requires that users access the page via https.
//		Requests sent using http will be redirected

define('FORCE_SSL', true);

////////////////////////////////////////////
//RESTRICT_USERS - 
//
//Default: False
//Description: Setting this to true will only allow access to users
//		set in the $users array found below.
//		This array contains a whitelist of user login names (not summoner names)
//		they are lowercased before being sha1 checksumed

define('RESTRICT_USERS', false);

//This array of strings controls user access. Use sha1() hashes of lowercased usernames
$users = array (
);

////////////////////////////////////////////
//
//END OF CONFIGURATION
//
////////////////////////////////////////////
