<?php /* Expects: $data, $errors, $submitLabel, optional $editId */ ?>
<form method="post" novalidate>
    <?= csrf_field() ?>
    <?php if (!empty($editId)): ?><input type="hidden" name="id" value="<?= (int) $editId ?>"><?php endif; ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label for="first_name" class="form-label">First name <span class="text-danger">*</span></label>
            <input type="text" class="form-control<?= field_class($errors, 'first_name') ?>" id="first_name" name="first_name"
                   maxlength="50" value="<?= e($data['first_name']) ?>" required>
            <?= field_error($errors, 'first_name') ?>
        </div>
        <div class="col-md-6">
            <label for="last_name" class="form-label">Last name <span class="text-danger">*</span></label>
            <input type="text" class="form-control<?= field_class($errors, 'last_name') ?>" id="last_name" name="last_name"
                   maxlength="50" value="<?= e($data['last_name']) ?>" required>
            <?= field_error($errors, 'last_name') ?>
        </div>
        <div class="col-md-6">
            <label for="contact_number" class="form-label">Contact number <span class="text-danger">*</span></label>
            <input type="text" class="form-control<?= field_class($errors, 'contact_number') ?>" id="contact_number" name="contact_number"
                   maxlength="25" placeholder="09171234567" value="<?= e($data['contact_number']) ?>" required>
            <?= field_error($errors, 'contact_number') ?>
        </div>
        <div class="col-md-6">
            <label for="date_registered" class="form-label">Date registered <span class="text-danger">*</span></label>
            <input type="date" class="form-control<?= field_class($errors, 'date_registered') ?>" id="date_registered" name="date_registered"
                   max="<?= date('Y-m-d') ?>" value="<?= e($data['date_registered']) ?>" required>
            <?= field_error($errors, 'date_registered') ?>
        </div>
        <div class="col-12">
            <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
            <input type="text" class="form-control<?= field_class($errors, 'address') ?>" id="address" name="address"
                   maxlength="255" value="<?= e($data['address']) ?>" required>
            <?= field_error($errors, 'address') ?>
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-success"><?= e($submitLabel) ?></button>
        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
