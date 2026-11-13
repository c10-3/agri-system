<?php /* Expects: $data, $errors, $lookups, $submitLabel, optional $editId */ ?>
<form method="post" novalidate>
    <?= csrf_field() ?>
    <?php if (!empty($editId)): ?><input type="hidden" name="id" value="<?= (int) $editId ?>"><?php endif; ?>
    <div class="row g-3">
        <div class="col-12">
            <label for="planting_id" class="form-label">Planting <span class="text-danger">*</span></label>
            <select class="form-select<?= field_class($errors, 'planting_id') ?>" id="planting_id" name="planting_id" required>
                <?= select_options($lookups['plantings'], 'planting_id', 'label', $data['planting_id'], '-- Select planting (farm, crop, date) --') ?>
            </select>
            <?= field_error($errors, 'planting_id') ?>
        </div>
        <div class="col-md-4">
            <label for="harvest_date" class="form-label">Harvest date <span class="text-danger">*</span></label>
            <input type="date" class="form-control<?= field_class($errors, 'harvest_date') ?>" id="harvest_date" name="harvest_date"
                   max="<?= date('Y-m-d') ?>" value="<?= e($data['harvest_date']) ?>" required>
            <?= field_error($errors, 'harvest_date') ?>
        </div>
        <div class="col-md-4">
            <label for="quantity_kg" class="form-label">Quantity (kg) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0.01" class="form-control<?= field_class($errors, 'quantity_kg') ?>" id="quantity_kg"
                   name="quantity_kg" value="<?= e($data['quantity_kg']) ?>" required>
            <?= field_error($errors, 'quantity_kg') ?>
        </div>
        <div class="col-md-4">
            <label for="selling_price_per_kg" class="form-label">Selling price per kg (₱) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0" class="form-control<?= field_class($errors, 'selling_price_per_kg') ?>" id="selling_price_per_kg"
                   name="selling_price_per_kg" value="<?= e($data['selling_price_per_kg']) ?>" required>
            <?= field_error($errors, 'selling_price_per_kg') ?>
        </div>
        <div class="col-md-6">
            <label for="grade_id" class="form-label">Quality grade <span class="text-danger">*</span></label>
            <select class="form-select<?= field_class($errors, 'grade_id') ?>" id="grade_id" name="grade_id" required>
                <?= select_options($lookups['grades'], 'grade_id', 'grade_name', $data['grade_id'], '-- Select grade --') ?>
            </select>
            <?= field_error($errors, 'grade_id') ?>
        </div>
        <div class="col-md-6">
            <label for="remark_id" class="form-label">Remark <span class="text-danger">*</span></label>
            <select class="form-select<?= field_class($errors, 'remark_id') ?>" id="remark_id" name="remark_id" required>
                <?= select_options($lookups['remarks'], 'remark_id', 'remark_description', $data['remark_id'], '-- Select remark --') ?>
            </select>
            <?= field_error($errors, 'remark_id') ?>
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-success"><?= e($submitLabel) ?></button>
        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
