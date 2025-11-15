<?php
require_once 'config.php';

if (needsSetup()) {
    header('Location: /app/install.php');
    exit;
}

requireLogin();

$user = getCurrentUser();
$siteId = $_GET['id'] ?? 0;
$period = $_GET['period'] ?? '7d'; // 7d, 30d, 90d, custom
$customStart = $_GET['start'] ?? '';
$customEnd = $_GET['end'] ?? '';

$db = getDb();

// Check access
if ($user['is_admin']) {
    $stmt = $db->prepare("SELECT * FROM sites WHERE id = ?");
    $stmt->execute([$siteId]);
} else {
    $stmt = $db->prepare("
        SELECT s.* FROM sites s
        INNER JOIN user_sites us ON s.id = us.site_id
        WHERE s.id = ? AND us.user_id = ?
    ");
    $stmt->execute([$siteId, $user['id']]);
}

$site = $stmt->fetch();

if (!$site) {
    header('Location: /index.php');
    exit;
}

// Calculate date range
if ($period === 'custom' && !empty($customStart) && !empty($customEnd)) {
    $startDate = date('Y-m-d 00:00:00', strtotime($customStart));
    $endDate = date('Y-m-d 23:59:59', strtotime($customEnd));
} else {
    $days = 7;
    if ($period === '30d') $days = 30;
    if ($period === '90d') $days = 90;
    
    $startDate = date('Y-m-d H:i:s', strtotime("-$days days"));
    $endDate = date('Y-m-d H:i:s');
}

// Get total pageviews
$stmt = $db->prepare("
    SELECT COUNT(*) as total 
    FROM pageviews 
    WHERE site_id = ? AND timestamp >= ? AND timestamp <= ?
");
$stmt->execute([$siteId, $startDate, $endDate]);
$totalViews = $stmt->fetch()['total'];

// Get unique visitors
$stmt = $db->prepare("
    SELECT COUNT(DISTINCT visitor_hash) as total 
    FROM pageviews 
    WHERE site_id = ? AND timestamp >= ? AND timestamp <= ?
");
$stmt->execute([$siteId, $startDate, $endDate]);
$uniqueVisitors = $stmt->fetch()['total'];

// Get bounce rate (sessions with only 1 pageview)
$stmt = $db->prepare("
    SELECT 
        COUNT(CASE WHEN pv_count = 1 THEN 1 END) * 100.0 / COUNT(*) as bounce_rate
    FROM (
        SELECT session_hash, COUNT(*) as pv_count
        FROM pageviews
        WHERE site_id = ? AND timestamp >= ? AND timestamp <= ?
        GROUP BY session_hash
    )
");
$stmt->execute([$siteId, $startDate, $endDate]);
$bounceRate = round($stmt->fetch()['bounce_rate'] ?? 0, 1);

// Get average session duration (in seconds)
$stmt = $db->prepare("
    SELECT AVG(duration) as avg_duration
    FROM (
        SELECT 
            session_hash,
            (JULIANDAY(MAX(timestamp)) - JULIANDAY(MIN(timestamp))) * 86400 as duration
        FROM pageviews
        WHERE site_id = ? AND timestamp >= ? AND timestamp <= ?
        GROUP BY session_hash
        HAVING COUNT(*) > 1
    )
");
$stmt->execute([$siteId, $startDate, $endDate]);
$avgDuration = round($stmt->fetch()['avg_duration'] ?? 0);

// Get top pages
$stmt = $db->prepare("
    SELECT path, COUNT(*) as views, COUNT(DISTINCT visitor_hash) as visitors
    FROM pageviews
    WHERE site_id = ? AND timestamp >= ? AND timestamp <= ?
    GROUP BY path
    ORDER BY views DESC
    LIMIT 10
");
$stmt->execute([$siteId, $startDate, $endDate]);
$topPages = $stmt->fetchAll();

// Get top referrers
$stmt = $db->prepare("
    SELECT 
        CASE 
            WHEN referrer = '' THEN 'Direct / None'
            ELSE referrer
        END as referrer,
        COUNT(*) as views
    FROM pageviews
    WHERE site_id = ? AND timestamp >= ? AND timestamp <= ?
    GROUP BY referrer
    ORDER BY views DESC
    LIMIT 10
");
$stmt->execute([$siteId, $startDate, $endDate]);
$topReferrers = $stmt->fetchAll();

// Get browser stats
$stmt = $db->prepare("
    SELECT browser, COUNT(*) as views
    FROM pageviews
    WHERE site_id = ? AND timestamp >= ? AND timestamp <= ?
    GROUP BY browser
    ORDER BY views DESC
    LIMIT 5
");
$stmt->execute([$siteId, $startDate, $endDate]);
$browsers = $stmt->fetchAll();

// Get OS stats
$stmt = $db->prepare("
    SELECT os, COUNT(*) as views
    FROM pageviews
    WHERE site_id = ? AND timestamp >= ? AND timestamp <= ?
    GROUP BY os
    ORDER BY views DESC
    LIMIT 5
");
$stmt->execute([$siteId, $startDate, $endDate]);
$operatingSystems = $stmt->fetchAll();

// Get device stats
$stmt = $db->prepare("
    SELECT device_type, COUNT(*) as views
    FROM pageviews
    WHERE site_id = ? AND timestamp >= ? AND timestamp <= ?
    GROUP BY device_type
    ORDER BY views DESC
");
$stmt->execute([$siteId, $startDate, $endDate]);
$devices = $stmt->fetchAll();

// Get daily views for chart
$stmt = $db->prepare("
    SELECT 
        DATE(timestamp) as date,
        COUNT(*) as views,
        COUNT(DISTINCT visitor_hash) as visitors
    FROM pageviews
    WHERE site_id = ? AND timestamp >= ? AND timestamp <= ?
    GROUP BY DATE(timestamp)
    ORDER BY date ASC
");
$stmt->execute([$siteId, $startDate, $endDate]);
$rawDailyStats = $stmt->fetchAll();

// Fill in missing dates with zeros
$dailyStats = [];
$currentDate = new DateTime(date('Y-m-d', strtotime($startDate)));
$endDateTime = new DateTime(date('Y-m-d', strtotime($endDate)));

// Create a map of existing data
$dataMap = [];
foreach ($rawDailyStats as $stat) {
    $dataMap[$stat['date']] = $stat;
}

// Fill all dates in range
while ($currentDate <= $endDateTime) {
    $dateStr = $currentDate->format('Y-m-d');
    if (isset($dataMap[$dateStr])) {
        $dailyStats[] = $dataMap[$dateStr];
    } else {
        $dailyStats[] = [
            'date' => $dateStr,
            'views' => 0,
            'visitors' => 0
        ];
    }
    $currentDate->modify('+1 day');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($site['name']) ?> - Analytics</title>
    <script src="/app/theme.js"></script>
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <link rel="stylesheet" href="/app/common.css">
    <style>
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        h1 {
            font-size: 28px;
            color: var(--text-primary);
        }
        .period-selector {
            display: flex;
            gap: 10px;
        }
        .period-btn {
            padding: 8px 16px;
            border: 2px solid var(--border-color);
            background: var(--bg-secondary);
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.2s;
        }
        .period-btn:hover {
            border-color: var(--accent-primary);
        }
        .period-btn.active {
            background: var(--accent-primary);
            color: var(--bg-primary);
            border-color: var(--accent-primary);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            padding: 24px;
        }
        .stat-label {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: var(--text-primary);
        }
        .stat-unit {
            font-size: 14px;
            color: #999;
            margin-left: 4px;
        }
        .card {
            padding: 24px;
        }
        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .page-url {
            word-break: break-all;
            overflow-wrap: anywhere;
            font-size: 14px;
        }
        .chart-container {
            height: 500px;
            margin-top: 20px;
            position: relative;
        }
        .chart-svg {
            width: 100%;
            height: 100%;
        }
        .line-path {
            transition: opacity 0.3s;
        }
        .axis path,
        .axis line {
            stroke: var(--border-color);
        }
        .axis text {
            fill: var(--text-secondary);
            font-size: 11px;
        }
        .axis-label {
            fill: var(--text-primary);
            font-size: 12px;
            font-weight: 600;
        }
        .chart-legend {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 16px;
            font-size: 14px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .legend-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 3px;
            opacity: 0.7;
        }
        .tooltip {
            position: absolute;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 12px;
            border-radius: 6px;
            font-size: 13px;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s;
            z-index: 1000;
        }
        .tooltip.visible {
            opacity: 1;
        }
        .date-range-form {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 20px;
            padding: 16px;
            background: var(--bg-secondary);
            border-radius: 8px;
            box-shadow: 0 2px 8px var(--shadow); 
            margin-bottom: 20px;
        }
        .date-range-form label {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-primary);
        }
        .date-range-form input[type="date"] {
            padding: 8px 12px;
            border: 2px solid var(--border-color);
            border-radius: 6px;
            font-size: 14px;
            background: var(--bg-primary);
            color: var(--text-primary);
        }
        .date-range-form input[type="date"]:focus {
            outline: none;
            border-color: var(--accent-primary);
        }
        .date-range-form button {
            padding: 8px 20px;
            background: var(--accent-primary);
            color: var(--bg-primary);
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
        }
        .date-range-form button:hover {
            background: var(--accent-hover);
        }
        @media (max-width: 768px) {
            .two-col {
                grid-template-columns: 1fr;
            }
            .date-range-form {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <?php require __DIR__ . '/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <div>
                <h1><?= htmlspecialchars($site['name']) ?></h1>
                <p style="color: var(--text-secondary); margin-top: 4px;"><?= htmlspecialchars($site['domain']) ?></p>
            </div>
            <div class="period-selector">
                <a href="/site/<?= $siteId ?>/7d" class="period-btn <?= $period === '7d' ? 'active' : '' ?>">7 Days</a>
                <a href="/site/<?= $siteId ?>/30d" class="period-btn <?= $period === '30d' ? 'active' : '' ?>">30 Days</a>
                <a href="/site/<?= $siteId ?>/90d" class="period-btn <?= $period === '90d' ? 'active' : '' ?>">90 Days</a>
            </div>
        </div>
        
        <form class="date-range-form" method="GET" action="/site/<?= $siteId ?>/">
            <input type="hidden" name="period" value="custom">
            <label>Custom Range:</label>
            <input type="date" name="start" value="<?= $customStart ?>" required>
            <label>to</label>
            <input type="date" name="end" value="<?= $customEnd ?>" required>
            <button type="submit">Apply</button>
            <?php if ($period === 'custom'): ?>
                <a href="/site/<?= $siteId ?>/" style="color: var(--accent-primary); text-decoration: none; font-size: 14px;">Clear</a>
            <?php endif; ?>
        </form>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Pageviews</div>
                <div class="stat-value"><?= number_format($totalViews) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Unique Visitors</div>
                <div class="stat-value"><?= number_format($uniqueVisitors) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Bounce Rate</div>
                <div class="stat-value"><?= $bounceRate ?><span class="stat-unit">%</span></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Avg. Session Duration</div>
                <div class="stat-value"><?= gmdate('i:s', $avgDuration) ?><span class="stat-unit">min</span></div>
            </div>
        </div>
        
        <div class="card">
            <h2>📈 Daily Traffic</h2>
            <?php if (empty($dailyStats)): ?>
                <p style="color: var(--text-secondary); text-align: center; padding: 40px;">No data available yet. Install the tracking script to start collecting data.</p>
            <?php else: ?>
                <div class="chart-container">
                    <svg id="traffic-chart" class="chart-svg"></svg>
                    <div class="tooltip" id="chart-tooltip"></div>
                </div>
                <div class="chart-legend">
                    <label class="legend-item" style="cursor: pointer;">
                        <input type="checkbox" id="toggle-pageviews" checked>
                        <div class="legend-color" style="background: var(--chart-dark);"></div>
                        <span>Pageviews</span>
                    </label>
                    <label class="legend-item" style="cursor: pointer;">
                        <input type="checkbox" id="toggle-visitors" checked>
                        <div class="legend-color" style="background: var(--chart-light);"></div>
                        <span>Unique Visitors</span>
                    </label>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="two-col">
            <div class="card">
                <h2>📄 Top Pages</h2>
                <?php if (empty($topPages)): ?>
                    <p style="color: var(--text-secondary);">No data yet</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Page</th>
                                <th style="text-align: right;">Views</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $maxViews = $topPages[0]['views'];
                            foreach ($topPages as $page): 
                                $percentage = ($page['views'] / $maxViews) * 100;
                            ?>
                                <tr>
                                    <td>
                                        <div class="page-url"><?= htmlspecialchars($page['path']) ?></div>
                                        <div class="bar" style="width: <?= $percentage ?>%"></div>
                                    </td>
                                    <td style="text-align: right; font-weight: 600;">
                                        <?= number_format($page['views']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2>🔗 Top Referrers</h2>
                <?php if (empty($topReferrers)): ?>
                    <p style="color: var(--text-secondary);">No data yet</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Source</th>
                                <th style="text-align: right;">Views</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $maxViews = $topReferrers[0]['views'];
                            foreach ($topReferrers as $ref): 
                                $percentage = ($ref['views'] / $maxViews) * 100;
                                $display = $ref['referrer'];
                                if ($display !== 'Direct / None') {
                                    $parsed = parse_url($display);
                                    $display = $parsed['host'] ?? $display;
                                }
                            ?>
                                <tr>
                                    <td>
                                        <div class="page-url"><?= htmlspecialchars($display) ?></div>
                                        <div class="bar" style="width: <?= $percentage ?>%"></div>
                                    </td>
                                    <td style="text-align: right; font-weight: 600;">
                                        <?= number_format($ref['views']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="two-col">
            <div class="card">
                <h2>🌐 Browsers</h2>
                <?php if (empty($browsers)): ?>
                    <p style="color: var(--text-secondary);">No data yet</p>
                <?php else: ?>
                    <table class="data-table">
                        <tbody>
                            <?php 
                            $maxViews = $browsers[0]['views'];
                            foreach ($browsers as $browser): 
                                $percentage = ($browser['views'] / $maxViews) * 100;
                            ?>
                                <tr>
                                    <td>
                                        <div><?= htmlspecialchars($browser['browser']) ?></div>
                                        <div class="bar" style="width: <?= $percentage ?>%"></div>
                                    </td>
                                    <td style="text-align: right; font-weight: 600;">
                                        <?= number_format($browser['views']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2>💻 Operating Systems</h2>
                <?php if (empty($operatingSystems)): ?>
                    <p style="color: var(--text-secondary);">No data yet</p>
                <?php else: ?>
                    <table class="data-table">
                        <tbody>
                            <?php 
                            $maxViews = $operatingSystems[0]['views'];
                            foreach ($operatingSystems as $os): 
                                $percentage = ($os['views'] / $maxViews) * 100;
                            ?>
                                <tr>
                                    <td>
                                        <div><?= htmlspecialchars($os['os']) ?></div>
                                        <div class="bar" style="width: <?= $percentage ?>%"></div>
                                    </td>
                                    <td style="text-align: right; font-weight: 600;">
                                        <?= number_format($os['views']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <h2>📱 Device Types</h2>
            <?php if (empty($devices)): ?>
                <p style="color: #666;">No data yet</p>
            <?php else: ?>
                <table class="data-table">
                    <tbody>
                        <?php 
                        $maxViews = $devices[0]['views'];
                        foreach ($devices as $device): 
                            $percentage = ($device['views'] / $maxViews) * 100;
                        ?>
                            <tr>
                                <td style="width: 200px;">
                                    <?= htmlspecialchars(ucfirst($device['device_type'])) ?>
                                </td>
                                <td>
                                    <div class="bar" style="width: <?= $percentage ?>%"></div>
                                </td>
                                <td style="text-align: right; font-weight: 600; width: 100px;">
                                    <?= number_format($device['views']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="/app/site-settings.php?id=<?= $siteId ?>" class="btn btn-secondary">⚙️ Settings</a>
            <a href="/index.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
    </div>
    
    <?php if (!empty($dailyStats)): ?>
    <script>
        // Parse PHP data into JavaScript
        const rawData = <?= json_encode($dailyStats) ?>;
        
        // Process data
        const chartData = rawData.map(d => ({
            date: new Date(d.date),
            pageviews: parseInt(d.views),
            visitors: parseInt(d.visitors)
        }));
        
        // Get colors from CSS variables
        const rootStyles = getComputedStyle(document.documentElement);
        const chartDark = rootStyles.getPropertyValue('--chart-dark').trim();
        const chartLight = rootStyles.getPropertyValue('--chart-light').trim();
        
        // Set up dimensions
        const container = document.querySelector('.chart-container');
        const margin = { top: 40, right: 40, bottom: 60, left: 60 };
        const width = container.clientWidth - margin.left - margin.right;
        const height = container.clientHeight - margin.top - margin.bottom;
        
        // Create SVG
        const svg = d3.select('#traffic-chart')
            .attr('width', width + margin.left + margin.right)
            .attr('height', height + margin.top + margin.bottom)
            .append('g')
            .attr('transform', `translate(${margin.left},${margin.top})`);
        
        // Create scales
        const xScale = d3.scaleTime()
            .domain(d3.extent(chartData, d => d.date))
            .range([0, width]);
        
        // Calculate max value for scaling
        const maxValue = d3.max(chartData, d => Math.max(d.pageviews, d.visitors));
        
        // Y scale
        const yScale = d3.scaleLinear()
            .domain([0, maxValue * 1.1])
            .range([height, 0])
            .nice();
        
        // Add grid lines
        svg.append('g')
            .attr('class', 'grid')
            .attr('opacity', 0.1)
            .call(d3.axisLeft(yScale)
                .tickSize(-width)
                .tickFormat(''));
        
        // Add X axis at bottom
        svg.append('g')
            .attr('class', 'axis')
            .attr('transform', `translate(0,${height})`)
            .call(d3.axisBottom(xScale)
                .ticks(Math.min(chartData.length, 10))
                .tickFormat(d3.timeFormat('%b %d')))
            .selectAll('text')
            .attr('transform', 'rotate(-45)')
            .style('text-anchor', 'end');
        
        // Add Y axis
        svg.append('g')
            .attr('class', 'axis')
            .call(d3.axisLeft(yScale)
                .ticks(5)
                .tickFormat(d => d.toLocaleString()));
        
        // Create line generators
        const linePageviews = d3.line()
            .x(d => xScale(d.date))
            .y(d => yScale(d.pageviews))
            .curve(d3.curveMonotoneX);
        
        const lineVisitors = d3.line()
            .x(d => xScale(d.date))
            .y(d => yScale(d.visitors))
            .curve(d3.curveMonotoneX);
        
        // Tooltip
        const tooltip = d3.select('#chart-tooltip');
        
        // Draw Pageviews line
        const pageviewsPath = svg.append('path')
            .datum(chartData)
            .attr('class', 'line-path')
            .attr('id', 'line-pageviews')
            .attr('fill', 'none')
            .attr('stroke', chartDark)
            .attr('stroke-width', 3)
            .attr('d', linePageviews);
        
        // Draw Visitors line  
        const visitorsPath = svg.append('path')
            .datum(chartData)
            .attr('class', 'line-path')
            .attr('id', 'line-visitors')
            .attr('fill', 'none')
            .attr('stroke', chartLight)
            .attr('stroke-width', 3)
            .attr('d', lineVisitors);
        
        // Animate lines
        [pageviewsPath, visitorsPath].forEach((path, i) => {
            const pathLength = path.node().getTotalLength();
            path
                .attr('stroke-dasharray', pathLength)
                .attr('stroke-dashoffset', pathLength)
                .transition()
                .duration(1500)
                .delay(i * 200)
                .ease(d3.easeCubicInOut)
                .attr('stroke-dashoffset', 0);
        });
        
        // Add dots for pageviews
        const pageviewsDots = svg.selectAll('.dot-pageviews')
            .data(chartData)
            .enter()
            .append('circle')
            .attr('class', 'dot-pageviews')
            .attr('cx', d => xScale(d.date))
            .attr('cy', d => yScale(d.pageviews))
            .attr('r', 4)
            .attr('fill', 'white')
            .attr('stroke', chartDark)
            .attr('stroke-width', 2)
            .attr('opacity', 0)
            .transition()
            .delay((d, i) => 1500 + i * 30)
            .duration(300)
            .attr('opacity', 1);
        
        // Add dots for visitors
        const visitorsDots = svg.selectAll('.dot-visitors')
            .data(chartData)
            .enter()
            .append('circle')
            .attr('class', 'dot-visitors')
            .attr('cx', d => xScale(d.date))
            .attr('cy', d => yScale(d.visitors))
            .attr('r', 4)
            .attr('fill', 'white')
            .attr('stroke', chartLight)
            .attr('stroke-width', 2)
            .attr('opacity', 0)
            .transition()
            .delay((d, i) => 1700 + i * 30)
            .duration(300)
            .attr('opacity', 1);
        
        // Add hover interaction overlay
        svg.append('rect')
            .attr('width', width)
            .attr('height', height)
            .attr('fill', 'transparent')
            .on('mousemove', function(event) {
                const [mouseX] = d3.pointer(event);
                const x0 = xScale.invert(mouseX);
                
                // Find closest data point
                const bisect = d3.bisector(d => d.date).left;
                let index = bisect(chartData, x0, 1);
                
                // Handle edge cases
                if (index === 0) {
                    index = 0;
                } else if (index >= chartData.length) {
                    index = chartData.length - 1;
                } else {
                    const d0 = chartData[index - 1];
                    const d1 = chartData[index];
                    // Pick closest point
                    if (x0 - d0.date > d1.date - x0) {
                        index = index;
                    } else {
                        index = index - 1;
                    }
                }
                
                const d = chartData[index];
                if (!d) return;
                
                // Format date
                const dateStr = d3.timeFormat('%B %d, %Y')(d.date);
                
                // Get mouse position relative to container
                const containerRect = container.getBoundingClientRect();
                const tooltipX = event.clientX - containerRect.left + 10;
                const tooltipY = event.clientY - containerRect.top - 10;
                
                // Show tooltip
                tooltip
                    .classed('visible', true)
                    .style('left', tooltipX + 'px')
                    .style('top', tooltipY + 'px')
                    .html(`
                        <div style="font-weight: 600; margin-bottom: 4px;">${dateStr}</div>
                        <div style="color: ${chartDark};">📊 Pageviews: ${d.pageviews.toLocaleString()}</div>
                        <div style="color: ${chartLight};">👥 Visitors: ${d.visitors.toLocaleString()}</div>
                    `);
            })
            .on('mouseout', function() {
                tooltip.classed('visible', false);
            });
        
        // Toggle functionality
        document.getElementById('toggle-pageviews').addEventListener('change', function() {
            const isChecked = this.checked;
            d3.select('#line-pageviews')
                .transition()
                .duration(300)
                .style('opacity', isChecked ? 1 : 0);
            d3.selectAll('.dot-pageviews')
                .transition()
                .duration(300)
                .style('opacity', isChecked ? 1 : 0);
        });
        
        document.getElementById('toggle-visitors').addEventListener('change', function() {
            const isChecked = this.checked;
            d3.select('#line-visitors')
                .transition()
                .duration(300)
                .style('opacity', isChecked ? 1 : 0);
            d3.selectAll('.dot-visitors')
                .transition()
                .duration(300)
                .style('opacity', isChecked ? 1 : 0);
        });
        
        
        // Handle window resize
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                location.reload();
            }, 250);
        });
    </script>
    <?php endif; ?>
</body>
</html>
