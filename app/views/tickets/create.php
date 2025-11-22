<?php
// FILE: /app/views/tickets/create.php
$pageTitle = 'Create Ticket';
include APP_PATH . '/views/layouts/header.php';
?>

<div class="card">
    <div class="card-header">Create New Ticket</div>

    <?php if (isset($errors) && !empty($errors)): ?>
    <div class="alert alert-error">
        <?php foreach ($errors as $field => $fieldErrors): ?>
            <?php foreach ($fieldErrors as $error): ?>
                <div><?php echo SecurityHelper::escape($error); ?></div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo BASE_URL; ?>/tickets/create">
        <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrf_token; ?>">

        <div class="form-group">
            <label class="form-label">Subject *</label>
            <input type="text" name="subject" class="form-control" required>
        </div>

        <div class="form-group">
            <label class="form-label">Customer Name</label>
            <input type="text" name="customer_name" class="form-control">
        </div>

        <div class="form-group">
            <label class="form-label">Customer Email *</label>
            <input type="email" name="customer_email" class="form-control" required>
        </div>

        <div class="form-group">
            <label class="form-label">Customer Phone</label>
            <input type="text" name="customer_phone" class="form-control">
        </div>

        <div class="form-group">
            <label class="form-label">Channel</label>
            <select name="channel_id" class="form-control">
                <option value="">Select Channel</option>
                <?php if (!empty($channels)): ?>
                    <?php foreach ($channels as $channel): ?>
                        <option value="<?php echo $channel['id']; ?>"><?php echo SecurityHelper::escape($channel['name']); ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Priority *</label>
            <select name="priority" class="form-control" required>
                <option value="low">Low</option>
                <option value="normal" selected>Normal</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Type</label>
            <select name="type" class="form-control">
                <option value="question">Question</option>
                <option value="incident">Incident</option>
                <option value="problem">Problem</option>
                <option value="task">Task</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Message *</label>
            <textarea name="message" class="form-control" required></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Create Ticket</button>
        <a href="<?php echo BASE_URL; ?>/tickets" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include APP_PATH . '/views/layouts/footer.php'; ?>
