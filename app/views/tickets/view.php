<?php
// FILE: /app/views/tickets/view.php
$pageTitle = 'Ticket ' . $ticket['public_id'];
include APP_PATH . '/views/layouts/header.php';
?>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between;">
        <span><?php echo SecurityHelper::escape($ticket['subject']); ?></span>
        <span class="badge badge-<?php echo $ticket['status']; ?>"><?php echo strtoupper($ticket['status']); ?></span>
    </div>

    <div style="margin-bottom: 20px;">
        <p><strong>Ticket ID:</strong> <?php echo SecurityHelper::escape($ticket['public_id']); ?></p>
        <p><strong>Customer:</strong> <?php echo SecurityHelper::escape($ticket['customer_name']); ?> (<?php echo SecurityHelper::escape($ticket['customer_email']); ?>)</p>
        <p><strong>Priority:</strong> <span class="badge badge-<?php echo $ticket['priority']; ?>"><?php echo strtoupper($ticket['priority']); ?></span></p>
        <p><strong>Created:</strong> <?php echo DateHelper::format($ticket['created_at'], 'Y-m-d H:i:s', $tenant['default_timezone'] ?? 'UTC'); ?></p>
    </div>

    <?php if ($slaStatus): ?>
    <div class="alert alert-info">
        <strong>SLA Status:</strong>
        First Response: <?php echo $slaStatus['first_response_met'] ? '✓ Met' : '✗ Pending'; ?> |
        Resolution: <?php echo $slaStatus['resolution_met'] ? '✓ Met' : '✗ Pending'; ?>
        <?php if ($slaStatus['breached']): ?>| <strong style="color: red;">BREACHED</strong><?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="messages-container">
        <?php if (!empty($messages)): ?>
            <?php foreach ($messages as $message): ?>
            <div class="message message-<?php echo $message['sender_type']; ?>">
                <div class="message-header">
                    <span class="message-sender">
                        <?php
                        if ($message['sender_type'] === 'customer') {
                            echo 'Customer';
                        } elseif ($message['sender_type'] === 'ai') {
                            echo 'AI Assistant';
                        } elseif ($message['sender_name']) {
                            echo SecurityHelper::escape($message['sender_name']);
                        } else {
                            echo 'System';
                        }
                        ?>
                    </span>
                    <span><?php echo DateHelper::timeAgo($message['created_at'], $tenant['default_timezone'] ?? 'UTC'); ?></span>
                </div>
                <div class="message-body"><?php echo nl2br(SecurityHelper::escape($message['body_text'])); ?></div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No messages yet.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">Reply to Ticket</div>
        <form method="POST" action="<?php echo BASE_URL; ?>/tickets/reply/<?php echo $ticket['public_id']; ?>">
            <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrf_token; ?>">

            <div class="form-group">
                <textarea name="message" class="form-control" placeholder="Type your reply..." required></textarea>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center;">
                <label style="display: flex; align-items: center; gap: 5px;">
                    <input type="checkbox" name="is_internal" value="1">
                    Internal note (not visible to customer)
                </label>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="aiSuggest()">AI Suggest</button>
                    <button type="submit" class="btn btn-primary">Send Reply</button>
                </div>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-header">Update Status</div>
        <form method="POST" action="<?php echo BASE_URL; ?>/tickets/updateStatus/<?php echo $ticket['public_id']; ?>" style="display: flex; gap: 10px; align-items: center;">
            <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrf_token; ?>">
            <select name="status" class="form-control" style="width: auto;">
                <option value="new" <?php echo $ticket['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                <option value="open" <?php echo $ticket['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                <option value="pending" <?php echo $ticket['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="on_hold" <?php echo $ticket['status'] === 'on_hold' ? 'selected' : ''; ?>>On Hold</option>
                <option value="resolved" <?php echo $ticket['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                <option value="closed" <?php echo $ticket['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Update</button>
        </form>
    </div>
</div>

<script>
function aiSuggest() {
    fetch('<?php echo BASE_URL; ?>/tickets/aiSuggest/<?php echo $ticket['public_id']; ?>')
        .then(response => response.json())
        .then(data => {
            if (data.suggestions && data.suggestions.length > 0) {
                const textarea = document.querySelector('textarea[name="message"]');
                textarea.value = data.suggestions[0].message;
            }
        })
        .catch(error => {
            alert('Failed to get AI suggestion');
        });
}
</script>

<?php include APP_PATH . '/views/layouts/footer.php'; ?>
