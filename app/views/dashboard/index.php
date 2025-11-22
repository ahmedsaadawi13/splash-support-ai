<?php
// FILE: /app/views/dashboard/index.php
$pageTitle = 'Dashboard';
include APP_PATH . '/views/layouts/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Tickets</div>
        <div class="stat-value"><?php echo $stats['total'] ?? 0; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Open Tickets</div>
        <div class="stat-value"><?php echo $stats['open_count'] ?? 0; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Resolved This Month</div>
        <div class="stat-value"><?php echo $stats['resolved_count'] ?? 0; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Urgent Priority</div>
        <div class="stat-value"><?php echo $stats['urgent_count'] ?? 0; ?></div>
    </div>
</div>

<div class="card">
    <div class="card-header">Recent Tickets</div>
    <table class="table">
        <thead>
            <tr>
                <th>Ticket ID</th>
                <th>Subject</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($recentTickets)): ?>
                <?php foreach ($recentTickets as $ticket): ?>
                <tr>
                    <td><?php echo SecurityHelper::escape($ticket['public_id']); ?></td>
                    <td><?php echo SecurityHelper::escape($ticket['subject']); ?></td>
                    <td><span class="badge badge-<?php echo $ticket['status']; ?>"><?php echo strtoupper($ticket['status']); ?></span></td>
                    <td><span class="badge badge-<?php echo $ticket['priority']; ?>"><?php echo strtoupper($ticket['priority']); ?></span></td>
                    <td><?php echo DateHelper::timeAgo($ticket['created_at'], $tenant['default_timezone'] ?? 'UTC'); ?></td>
                    <td><a href="<?php echo BASE_URL; ?>/tickets/view/<?php echo $ticket['public_id']; ?>" class="btn btn-sm btn-secondary">View</a></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" class="text-center">No tickets found</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (isset($subscription) && isset($usage)): ?>
<div class="card">
    <div class="card-header">Usage & Subscription</div>
    <p><strong>Plan:</strong> <?php echo SecurityHelper::escape($subscription['plan_name']); ?></p>
    <p><strong>Tickets this month:</strong> <?php echo $usage['current_ticket_count_month']; ?> <?php if ($subscription['max_tickets_per_month']): ?>/ <?php echo $subscription['max_tickets_per_month']; ?><?php endif; ?></p>
    <p><strong>AI Tokens used:</strong> <?php echo $usage['current_ai_tokens_used_month']; ?> <?php if ($subscription['max_ai_tokens_per_month']): ?>/ <?php echo $subscription['max_ai_tokens_per_month']; ?><?php endif; ?></p>
    <p><strong>Status:</strong> <span class="badge badge-<?php echo $subscription['status']; ?>"><?php echo strtoupper($subscription['status']); ?></span></p>
</div>
<?php endif; ?>

<?php include APP_PATH . '/views/layouts/footer.php'; ?>
