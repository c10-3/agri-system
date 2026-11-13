<?php /* Expects: $data, $errors, $lookups, $submitLabel, optional $editId */ ?>
<form method="post" novalidate>
    <?= csrf_field() ?>
    <?php if (!empty($editId)): ?><input type="hidden" name="id" value="<?= (int) $editId ?>"><?php endif; ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label for="farm_id" class="form-label">Farm <span class="text-danger">*</span></label>
            <select class="form-select<?= field_class($errors, 'farm_id') ?>" id="farm_id" name="farm_id" required>
                <?= select_options($lookups['farms'], 'farm_id', 'label', $data['farm_id'], '-- Select farm --') ?>
            </select>
            <?= field_error($errors, 'farm_id') ?>
        </div>
        <div class="col-md-6">
            <label for="crop_id" class="form-label">Crop <span class="text-danger">*</span></label>
            <select class="form-select<?= field_class($errors, 'crop_id') ?>" id="crop_id" name="crop_id" required>
                <?= select_options($lookups['crops'], 'crop_id', 'label', $data['crop_id'], '-- Select crop --') ?>
            </select>
            <?= field_error($errors, 'crop_id') ?>
        </div>
        <div class="col-md-4">
            <label for="planting_date" class="form-label">Planting date <span class="text-danger">*</span></label>
            <input type="date" class="form-control<?= field_class($errors, 'planting_date') ?>" id="planting_date" name="planting_date"
                   value="<?= e($data['planting_date']) ?>" required>
            <?= field_error($errors, 'planting_date') ?>
        </div>
        <div class="col-md-4">
            <label for="expected_harvest_date" class="form-label">Expected harvest date</label>
            <input type="date" class="form-control<?= field_class($errors, 'expected_harvest_date') ?>" id="expected_harvest_date"
                   name="expected_harvest_date" value="<?= e($data['expected_harvest_date']) ?>">
            <?= field_error($errors, 'expected_harvest_date') ?>
        </div>
        <div class="col-md-4">
            <label for="status_id" class="form-label">Status <span class="text-danger">*</span></label>
            <select class="form-select<?= field_class($errors, 'status_id') ?>" id="status_id" name="status_id" required>
                <?= select_options($lookups['statuses'], 'status_id', 'status_name', $data['status_id'], '-- Select status --') ?>
            </select>
            <?= field_error($errors, 'status_id') ?>
        </div>
        <div class="col-md-6">
            <label for="area_planted" class="form-label">Area planted (hectares) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0.01" class="form-control<?= field_class($errors, 'area_planted') ?>" id="area_planted"
                   name="area_planted" value="<?= e($data['area_planted']) ?>" required>
            <?= field_error($errors, 'area_planted') ?>
        </div>
        <div class="col-md-6">
            <label for="seed_quantity" class="form-label">Seed quantity <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0" class="form-control<?= field_class($errors, 'seed_quantity') ?>" id="seed_quantity"
                   name="seed_quantity" value="<?= e($data['seed_quantity']) ?>" required>
            <?= field_error($errors, 'seed_quantity') ?>
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-success"><?= e($submitLabel) ?></button>
        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
