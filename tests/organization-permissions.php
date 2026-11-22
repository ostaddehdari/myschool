<?php
declare(strict_types=1);
define('ABSPATH', __DIR__ . '/');
require_once dirname(__DIR__) . '/src/Contracts/ModuleInterface.php';
require_once dirname(__DIR__) . '/src/Core/Capabilities.php';
require_once dirname(__DIR__) . '/src/Modules/Organization/OrganizationModule.php';

use MySchool\Core\Capabilities;
use MySchool\Modules\Organization\OrganizationModule;

$catalog = Capabilities::labels();
foreach (Capabilities::all() as $cap) {
    if (!isset($catalog[$cap]) || !preg_match('/[\x{0600}-\x{06FF}]/u', $catalog[$cap])) {
        throw new RuntimeException("Missing Persian label: {$cap}");
    }
}
$allow = OrganizationModule::availableCapabilities();
foreach ($allow as $cap) {
    if (!in_array($cap, Capabilities::all(), true)) {
        throw new RuntimeException("Unknown capability: {$cap}");
    }
}
foreach (['myschool_manage_school', 'myschool_manage_staff', 'myschool_manage_integrations',
          'myschool_manage_sms', 'myschool_view_own_children'] as $sensitive) {
    if (in_array($sensitive, $allow, true)) {
        throw new RuntimeException("Sensitive capability delegated: {$sensitive}");
    }
}
echo "Permissions catalog smoke test passed.\n";
