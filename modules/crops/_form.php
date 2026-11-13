<?php /* Expects: $data, $errors, $lookups, $submitLabel, optional $editId */ ?>
<form method="post" novalidate>
    <?= csrf_field() ?>
    <?php if (!empty($editId)): ?><input type="hidden" name="id" value="<?= (int) $editId ?>"><?php endif; ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label for="crop_name" class="form-label">Crop name <span class="text-danger">*</span></label>
            <input type="text" class="form-control<?= field_class($errors, 'crop_name') ?>" id="crop_name" name="crop_name"
                   maxlength="100" value="<?= e($data['crop_name']) ?>" required>
            <?= field_error($errors, 'crop_name') ?>
        </div>
        <div class="col-md-6">
            <label for="variety" class="form-label">Variety</label>
            <input type="text" class="form-control<?= field_class($errors, 'variety') ?>" id="variety" name="variety"
                   maxlength="100" value="<?= e($data['variety']) ?>">
            <?= field_error($errors, 'variety') ?>
        </div>
        <div class="col-md-4">
            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
            <select class="form-select<?= field_class($errors, 'category_id') ?>" id="category_id" name="category_id" required>
                <?= select_options($lookups['categories'], 'category_id', 'category_name', $data['category_id'], '-- Select category --') ?>
            </select>
            <?= field_error($errors, 'category_id') ?>
        </div>
        <div class="col-md-4">
            <label for="season_id" class="form-label">Season <span class="text-danger">*</span></label>
            <select class="form-select<?= field_class($errors, 'season_id') ?>" id="season_id" name="season_id" required>
                <?= select_options($lookups['seasons'], 'season_id', 'season_name', $data['season_id'], '-- Select season --') ?>
            </select>
            <?= field_error($errors, 'season_id') ?>
        </div>
        <div class="col-md-4">
            <label for="growing_period_days" class="form-label">Growing period (days) <span class="text-danger">*</span></label>
            <input type="number" min="1" max="730" step="1" class="form-control<?= field_class($errors, 'growing_period_days') ?>"
                   id="growing_period_days" name="growing_period_days" value="<?= e($data['growing_period_days']) ?>" required>
            <?= field_error($errors, 'growing_period_days') ?>
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-success"><?= e($submitLabel) ?></button>
        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
