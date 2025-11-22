<?php
// FILE: /app/views/tickets/index.php
$pageTitle = 'Tickets';
include APP_PATH . '/views/layouts/header.php';
?>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <span>All Tickets</span>
        <a href="<?php echo BASE_URL; ?>/tickets/create" class="btn btn-primary btn-sm">Create Ticket</a>
    </div>

    <form method="GET" style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
        <select name="status" class="form-control" style="width: auto;">
            <option value="">All Statuses</option>
            <option value="new" <?php echo ($filters['status'] ?? '') === 'new' ? 'selected' : ''; ?>>New</option>
            <option value="open" <?php echo ($filters['status'] ?? '') === 'open' ? 'selected' : ''; ?>>Open</option>
            <option value="pending" <?php echo ($filters['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="resolved" <?php echo ($filters['status'] ?? '') === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
            <option value="closed" <?php echo ($filters['status'] ?? '') === 'closed' ? 'selected' : ''; ?>>Closed</option>
        </select>

        <select name="priority" class="form-control" style="width: auto;">
            <option value="">All Priorities</option>
            <option value="low" <?php echo ($filters['priority'] ?? '') === 'low' ? 'selected' : ''; ?>>Low</option>
            <option value="normal" <?php echo ($filters['priority'] ?? '') === 'normal' ? 'selected' : ''; ?>>Normal</option>
            <option value="high" <?php echo ($filters['priority'] ?? '') === 'high' ? 'selected' : ''; ?>>High</option>
            <option value="urgent" <?php echo ($filters['priority'] ?? '') === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
        </select>

        <label style="display: flex; align-items: center; gap: 5px;">
            <input type="checkbox" name="assigned_to_me" value="1" <?php echo ($filters['assigned_to_me'] ?? false) ? 'checked' : ''; ?>>
            Assigned to me
        </label>

        <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Subject</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Channel</th>
                <th>Assignee</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($tickets)): ?>
                <?php foreach ($tickets as $ticket): ?>
                <tr>
                    <td><?php echo SecurityHelper::escape($ticket['public_id']); ?></td>
                    <td><?php echo SecurityHelper::escape($ticket['subject']); ?></td>
                    <td><?php echo SecurityHelper::escape($ticket['customer_email']); ?></td>
                    <td><span class="badge badge-<?php echo $ticket['status']; ?>"><?php echo strtoupper($ticket['status']); ?></span></td>
                    <td><span class="badge badge-<?php echo $ticket['priority']; ?>"><?php echo strtoupper($ticket['priority']); ?></span></td>
                    <td><?php echo SecurityHelper::escape($ticket['channel_name'] ?? 'N/A'); ?></td>
                    <td><?php echo SecurityHelper::escape($ticket['assignee_name'] ?? 'Unassigned'); ?></td>
                    <td><?php echo DateHelper::timeAgo($ticket['created_at'], $tenant['default_timezone'] ?? 'UTC'); ?></td>
                    <td><a href="<?php echo BASE_URL; ?>/tickets/view/<?php echo $ticket['public_id']; ?>" class="btn btn-sm btn-secondary">View</a></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="9" class="text-center">No tickets found</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include APP_PATH . '/views/layouts/footer.php'; ?>
