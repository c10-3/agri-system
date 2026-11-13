<?php /* Expects: $data, $errors, $lookups, $submitLabel, optional $editId */ ?>
<form method="post" novalidate>
    <?= csrf_field() ?>
    <?php if (!empty($editId)): ?><input type="hidden" name="id" value="<?= (int) $editId ?>"><?php endif; ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label for="farmer_id" class="form-label">Farmer <span class="text-danger">*</span></label>
            <select class="form-select<?= field_class($errors, 'farmer_id') ?>" id="farmer_id" name="farmer_id" required>
                <?= select_options($lookups['farmers'], 'farmer_id', 'label', $data['farmer_id'], '-- Select farmer --') ?>
            </select>
            <?= field_error($errors, 'farmer_id') ?>
        </div>
        <div class="col-md-6">
            <label for="farm_name" class="form-label">Farm name <span class="text-danger">*</span></label>
            <input type="text" class="form-control<?= field_class($errors, 'farm_name') ?>" id="farm_name" name="farm_name"
                   maxlength="100" value="<?= e($data['farm_name']) ?>" required>
            <?= field_error($errors, 'farm_name') ?>
        </div>
        <div class="col-md-8">
            <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
            <input type="text" class="form-control<?= field_class($errors, 'location') ?>" id="location" name="location"
                   maxlength="255" value="<?= e($data['location']) ?>" required>
            <?= field_error($errors, 'location') ?>
        </div>
        <div class="col-md-4">
            <label for="area_hectares" class="form-label">Area (hectares) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0.01" class="form-control<?= field_class($errors, 'area_hectares') ?>" id="area_hectares"
                   name="area_hectares" value="<?= e($data['area_hectares']) ?>" required>
            <?= field_error($errors, 'area_hectares') ?>
        </div>
        <div class="col-md-6">
            <label for="soil_type_id" class="form-label">Soil type <span class="text-danger">*</span></label>
            <select class="form-select<?= field_class($errors, 'soil_type_id') ?>" id="soil_type_id" name="soil_type_id" required>
                <?= select_options($lookups['soils'], 'soil_type_id', 'soil_type_name', $data['soil_type_id'], '-- Select soil type --') ?>
            </select>
            <?= field_error($errors, 'soil_type_id') ?>
        </div>
        <div class="col-md-6">
            <label for="irrigation_id" class="form-label">Irrigation type <span class="text-danger">*</span></label>
            <select class="form-select<?= field_class($errors, 'irrigation_id') ?>" id="irrigation_id" name="irrigation_id" required>
                <?= select_options($lookups['irrigations'], 'irrigation_id', 'irrigation_name', $data['irrigation_id'], '-- Select irrigation --') ?>
            </select>
            <?= field_error($errors, 'irrigation_id') ?>
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-success"><?= e($submitLabel) ?></button>
        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
