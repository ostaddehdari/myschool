<?php
namespace MySchool\Modules\Organization;

use MySchool\Contracts\ModuleInterface;
use MySchool\Core\Capabilities;
use MySchool\Core\AuditLog;

if (!defined('ABSPATH')) { exit; }

/**
 * Custom organizational positions for teachers and staff.
 * Position role slugs are stable; adding one never replaces a user's base school role.
 */
final class OrganizationModule implements ModuleInterface {
    public const OPTION = 'myschool_organization_positions';
    private const SYNC_OPTION = 'myschool_positions_sync_hash';
    private const PREFIX = 'myschool_position_';

    public function id(): string { return 'organization'; }

    public function register(): void {
        add_action('init', [self::class, 'syncRoles'], 15);
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_post_myschool_save_position', [$this, 'save']);
        add_action('admin_post_myschool_delete_position', [$this, 'delete']);
        add_action('admin_post_myschool_assign_position', [$this, 'assign']);
    }

    public static function positions(): array {
        $raw = get_option(self::OPTION, []);
        return is_array($raw) ? $raw : [];
    }

    public static function availableCapabilities(): array {
        $groups = Capabilities::groups();
        return array_values(array_unique(array_merge(
            $groups['teaching'], $groups['reports'],
            ['myschool_manage_classes', 'myschool_manage_students', 'myschool_manage_enrollments'],
            ['myschool_view_canteen', 'myschool_manage_canteen', 'myschool_manage_tickets',
             'myschool_create_ticket', 'myschool_send_messages']
        )));
    }

    public static function roleSlug(string $id): string {
        return self::PREFIX . $id;
    }

    private static function cleanId($value): string {
        $id = sanitize_key((string) $value);
        return preg_match('/^[a-z][a-z0-9_]{1,29}$/D', $id) ? $id : '';
    }

    private static function canManage(): bool {
        return current_user_can('myschool_manage_school');
    }

    private static function redirect(string $status): void {
        wp_safe_redirect(add_query_arg('myschool_status', $status, admin_url('admin.php?page=myschool-positions')));
        exit;
    }

    public static function syncRoles(): void {
        $positions = self::positions();
        $hash = md5(wp_json_encode($positions));
        if (get_option(self::SYNC_OPTION, '') === $hash) { return; }

        $allowed = self::availableCapabilities();
        foreach ($positions as $id => $position) {
            if (self::cleanId($id) !== $id || !is_array($position)) { continue; }
            $slug = self::roleSlug($id);
            $name = sanitize_text_field((string) ($position['title'] ?? ''));
            if ($name === '') { continue; }
            $caps = array_values(array_intersect((array) ($position['caps'] ?? []), $allowed));
            $role = get_role($slug);
            if (!$role) {
                add_role($slug, $name, ['read' => true, 'myschool_access' => true]);
                $role = get_role($slug);
            }
            if (!$role) { continue; }

            // Only manage capabilities in our own catalog. Do not alter unrelated privileges.
            foreach ($allowed as $cap) {
                if (in_array($cap, $caps, true)) { $role->add_cap($cap); }
                else { $role->remove_cap($cap); }
            }
            $role->add_cap('read');
            $role->add_cap('myschool_access');

            // WordPress roles keep a separate persisted display name.
            $wp_roles = wp_roles();
            if (isset($wp_roles->roles[$slug])) {
                $wp_roles->roles[$slug]['name'] = $name;
                if (isset($wp_roles->role_objects[$slug])) {
                    $wp_roles->role_objects[$slug]->name = $name;
                }
                if ($wp_roles->use_db) {
                    update_option($wp_roles->role_key, $wp_roles->roles);
                }
            }
        }
        update_option(self::SYNC_OPTION, $hash, false);
    }

    public function menu(): void {
        add_submenu_page('myschool', 'پست‌ها و دسترسی‌های سازمانی', 'پست‌های سازمانی',
            'myschool_manage_school', 'myschool-positions', [$this, 'render']);
    }

    public function save(): void {
        if (!self::canManage()) { wp_die('دسترسی غیرمجاز'); }
        check_admin_referer('myschool_save_position');

        $id = self::cleanId(wp_unslash($_POST['position_id'] ?? ''));
        $title = sanitize_text_field(wp_unslash($_POST['position_title'] ?? ''));
        if ($id === '' || $title === '') { self::redirect('invalid'); }
        $raw = isset($_POST['position_caps']) && is_array($_POST['position_caps'])
            ? array_map('sanitize_key', wp_unslash($_POST['position_caps'])) : [];
        $allowed = self::availableCapabilities();
        $caps = array_values(array_unique(array_intersect($raw, $allowed)));
        $positions = self::positions();
        $positions[$id] = ['title' => $title, 'caps' => $caps];
        update_option(self::OPTION, $positions, false);
        delete_option(self::SYNC_OPTION);
        self::syncRoles();
        AuditLog::record('positions.saved', 'position', 0, ['source' => $id]);
        self::redirect('saved');
    }

    public function delete(): void {
        if (!self::canManage()) { wp_die('دسترسی غیرمجاز'); }
        check_admin_referer('myschool_delete_position');

        $id = self::cleanId(wp_unslash($_POST['position_id'] ?? ''));
        $positions = self::positions();
        if ($id === '' || !isset($positions[$id])) { self::redirect('invalid'); }
        $slug = self::roleSlug($id);

        // Never delete a position that is still assigned to someone.
        if (get_users(['role' => $slug, 'fields' => 'ID', 'number' => 1])) {
            self::redirect('assigned');
        }
        unset($positions[$id]);
        update_option(self::OPTION, $positions, false);
        remove_role($slug);
        delete_option(self::SYNC_OPTION);
        AuditLog::record('positions.deleted', 'position', 0, ['source' => $id]);
        self::redirect('deleted');
    }

    public function assign(): void {
        if (!self::canManage()) { wp_die('دسترسی غیرمجاز'); }
        check_admin_referer('myschool_assign_position');

        $user_id = absint($_POST['staff_user_id'] ?? 0);
        $id = self::cleanId(wp_unslash($_POST['position_id'] ?? ''));
        $action = sanitize_key(wp_unslash($_POST['position_action'] ?? 'add'));
        $positions = self::positions();
        $user = get_user_by('id', $user_id);
        if (!$user || !isset($positions[$id]) || !in_array($action, ['add', 'remove'], true)) {
            self::redirect('invalid');
        }
        if (!array_intersect(['myschool_teacher', 'myschool_staff'], $user->roles)) {
            self::redirect('not_staff');
        }

        $slug = self::roleSlug($id);
        if ($action === 'add') { $user->add_role($slug); }
        else { $user->remove_role($slug); }
        AuditLog::record('positions.assigned', 'user', $user_id, ['source' => $id . ':' . $action]);
        self::redirect('assigned_ok');
    }

    private static function capLabel(string $cap): string {
        return Capabilities::labels()[$cap] ?? $cap;
    }

    private static function form(string $id, array $position, array $allowed): void {
        $title = $position['title'] ?? '';
        $selected = (array) ($position['caps'] ?? []);
        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('myschool_save_position'); ?>
            <input type="hidden" name="action" value="myschool_save_position">
            <p><label>شناسه لاتین ثابت
                <input name="position_id" required pattern="[a-z][a-z0-9_]{1,29}"
                    value="<?php echo esc_attr($id); ?>" <?php echo $id !== '' ? 'readonly' : ''; ?>
                    placeholder="training_coordinator"></label></p>
            <p><label>عنوان فارسی پست
                <input name="position_title" required class="regular-text"
                    value="<?php echo esc_attr($title); ?>" placeholder="مسئول آموزش"></label></p>
            <fieldset><legend><strong>دسترسی‌های این پست</strong></legend>
                <p class="description">فقط گزینه‌های انتخاب‌شده اعطا می‌شوند. دسترسی مدیرکل و تنظیمات امنیتی قابل واگذاری نیستند.</p>
                <?php foreach ($allowed as $cap) : ?>
                    <label style="display:inline-flex;min-width:240px;padding:6px 0;gap:5px;align-items:center">
                        <input type="checkbox" name="position_caps[]" value="<?php echo esc_attr($cap); ?>"
                            <?php checked(in_array($cap, $selected, true)); ?>>
                        <?php echo esc_html(self::capLabel($cap)); ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>
            <?php submit_button($id === '' ? 'ساخت پست سازمانی' : 'ذخیره ویرایش پست'); ?>
        </form>
        <?php
    }

    public function render(): void {
        if (!self::canManage()) { wp_die('دسترسی غیرمجاز'); }
        $positions = self::positions();
        $allowed = self::availableCapabilities();
        $status = sanitize_key(wp_unslash($_GET['myschool_status'] ?? ''));
        $messages = [
            'saved' => 'پست سازمانی ذخیره شد.', 'deleted' => 'پست حذف شد.',
            'assigned' => 'این پست به کاربر اختصاص دارد؛ ابتدا انتساب‌ها را حذف کنید.',
            'assigned_ok' => 'انتساب پست به‌روزرسانی شد.',
            'not_staff' => 'کاربر باید از قبل معلم یا کارمند مدرسه باشد.',
            'invalid' => 'اطلاعات واردشده معتبر نیست.'
        ];
        ?>
        <div class="wrap myschool-admin" dir="rtl">
            <h1>پست‌ها و دسترسی‌های سازمانی</h1>
            <p>هر پست یک نقش با عنوان فارسی و مجوزهای روشن است. کاربر می‌تواند چند پست داشته باشد؛ نقش پایه معلم یا کارمند حذف نمی‌شود.</p>
            <?php if (isset($messages[$status])) : ?>
                <div class="notice notice-info"><p><?php echo esc_html($messages[$status]); ?></p></div>
            <?php endif; ?>
            <div class="myschool-panel">
                <h2>پست جدید</h2>
                <?php self::form('', [], $allowed); ?>
            </div>
            <div class="myschool-panel">
                <h2>پست‌های تعریف‌شده</h2>
                <?php if (!$positions) : ?><p>هنوز پستی تعریف نشده است.</p><?php endif; ?>
                <?php foreach ($positions as $id => $position) : ?>
                    <details style="padding:12px;border-bottom:1px solid #ddd">
                        <summary><strong><?php echo esc_html($position['title']); ?></strong>
                            — <?php echo esc_html(count((array) ($position['caps'] ?? []))); ?> دسترسی
                        </summary>
                        <?php self::form($id, $position, $allowed); ?>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <?php wp_nonce_field('myschool_delete_position'); ?>
                            <input type="hidden" name="action" value="myschool_delete_position">
                            <input type="hidden" name="position_id" value="<?php echo esc_attr($id); ?>">
                            <?php submit_button('حذف پست بدون انتساب', 'delete', 'submit', false); ?>
                        </form>
                    </details>
                <?php endforeach; ?>
            </div>
            <?php if ($positions) : ?>
                <div class="myschool-panel">
                    <h2>انتصاب پست به معلم یا کارمند</h2>
                    <p>شناسه کاربر وردپرس را از فهرست کاربران وارد کنید. این عملیات نقش پایه کاربر را نگه می‌دارد.</p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('myschool_assign_position'); ?>
                        <input type="hidden" name="action" value="myschool_assign_position">
                        <label>شناسه کاربر <input type="number" min="1" name="staff_user_id" required></label>
                        <label>پست <select name="position_id">
                            <?php foreach ($positions as $id => $position) : ?>
                                <option value="<?php echo esc_attr($id); ?>"><?php echo esc_html($position['title']); ?></option>
                            <?php endforeach; ?>
                        </select></label>
                        <label>عملیات <select name="position_action">
                            <option value="add">افزودن مسئولیت</option><option value="remove">حذف مسئولیت</option>
                        </select></label>
                        <?php submit_button('ثبت تغییر مسئولیت'); ?>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
