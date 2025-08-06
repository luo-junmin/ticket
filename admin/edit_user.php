<?php
// admin/edit_user.php
?>

<form method="post" action="/admin/update_user.php">
    <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">

    <div class="form-group">
        <label>角色</label>
        <select name="role" class="form-control">
            <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>普通用户</option>
            <option value="inspector" <?= $user['role'] === 'inspector' ? 'selected' : '' ?>>验票员</option>
            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>管理员</option>
        </select>
    </div>

    <button type="submit" class="btn btn-primary">保存更改</button>
</form>