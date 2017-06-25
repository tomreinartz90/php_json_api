<?php

/**
 * Created by PhpStorm.
 * User: taren
 * Date: 23-6-2017
 * Time: 19:23
 */
class AuthTemplate
{
  function __construct()
  {
  }

  public function getLoginTemplate(){
    return "
    <form class=\"callout text-center\" method='post'>
  <h2>Login</h2>
  <div class=\"floated-label-wrapper\">
    <label for=\"email\">Email</label>
    <input type=\"email\" id=\"email\" name=\"email\" placeholder=\"Email\">
  </div>
  <div class=\"floated-label-wrapper\">
    <label for=\"pass\">Password</label>
    <input type=\"password\" id=\"pass\" name=\"password\" placeholder=\"Password\">
  </div>
  <input class=\"button expanded\" type=\"submit\" value=\"Sign In\">
</form>

    ";
  }
}