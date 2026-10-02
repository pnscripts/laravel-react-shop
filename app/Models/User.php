<?php

namespace App\Models;

use PnShop\Customer\Models\User as CustomerUser;

/**
 * The shop's customer model (auth.providers.users). Add your own relations and logic here;
 * the PN Shop behaviour comes from PnShop\Customer\Models\User.
 */
class User extends CustomerUser {}
