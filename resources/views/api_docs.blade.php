<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation - HamQadam</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #1a1a2e;
            background: #f5f6fa;
        }
        .layout {
            display: flex;
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            gap: 30px;
        }
        .sidebar {
            width: 260px;
            flex-shrink: 0;
            position: sticky;
            top: 20px;
            align-self: flex-start;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            padding: 20px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }
        .sidebar-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #667eea;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #667eea;
        }
        .sidebar-link {
            display: block;
            font-size: 13px;
            color: #555;
            text-decoration: none;
            padding: 5px 8px;
            border-radius: 4px;
            margin-bottom: 2px;
            transition: all 0.15s;
            cursor: pointer;
        }
        .sidebar-link:hover,
        .sidebar-link.active {
            background: #f0f1f5;
            color: #1a1a2e;
        }
        .sidebar-link.h2 { padding-left: 8px; font-weight: 500; }
        .sidebar-link.h3 { padding-left: 20px; font-size: 12px; color: #777; }
        .main {
            flex: 1;
            min-width: 0;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 30px 40px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        .header h1 { font-size: 28px; margin-bottom: 5px; }
        .header p { opacity: 0.85; font-size: 14px; }
        .content {
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        .content h1 { font-size: 24px; margin: 30px 0 15px; padding-bottom: 8px; border-bottom: 2px solid #667eea; }
        .content h1:first-child { margin-top: 0; }
        .content h2 { font-size: 20px; margin: 25px 0 10px; color: #2d3436; }
        .content h3 { font-size: 16px; margin: 20px 0 8px; color: #636e72; }
        .content p { margin-bottom: 12px; }
        .content table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0 25px;
            font-size: 14px;
        }
        .content table th {
            background: #f0f1f5;
            text-align: left;
            padding: 10px 12px;
            font-weight: 600;
            border: 1px solid #e0e0e0;
        }
        .content table td {
            padding: 10px 12px;
            border: 1px solid #e0e0e0;
            vertical-align: top;
        }
        .content table tr:nth-child(even) { background: #fafafa; }
        .content code {
            background: #f0f1f5;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 13px;
            color: #e17055;
        }
        .content pre {
            background: #2d3436;
            color: #dfe6e9;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 10px 0 20px;
            font-size: 13px;
        }
        .content ul, .content ol { margin: 10px 0 15px 20px; }
        .content li { margin-bottom: 4px; }
        .content a { color: #667eea; }
        hr { border: none; border-top: 1px solid #eee; margin: 30px 0; }
        @media (max-width: 900px) {
            .layout { flex-direction: column; padding: 15px; }
            .sidebar {
                width: 100%;
                position: static;
                max-height: none;
            }
            .content { padding: 20px; }
            .content table { font-size: 12px; }
            .content table th, .content table td { padding: 6px 8px; }
        }
    </style>
</head>
<body>
    <div class="layout">
        <nav class="sidebar" id="sidebar"></nav>
        <div class="main">
            <div class="header">
                <h1>HamQadam API Documentation</h1>
                <p>REST API reference for the HamQadam matrimonial platform</p>
            </div>
            <div class="content" id="content">
                {!! str_replace('{{APP_URL}}', $app_url, $content) !!}
            </div>
        </div>
    </div>
    <script>
        (function() {
            var sidebar = document.getElementById('sidebar');
            var content = document.getElementById('content');
            var headings = content.querySelectorAll('h1, h2, h3');
            var toc = [];
            var sidebarHtml = '<div class="sidebar-title">On this page</div>';

            headings.forEach(function(h, i) {
                var text = h.textContent.trim();
                if (!text || text === 'HamQadam API Documentation') return;
                var id = 'heading-' + i;
                h.setAttribute('id', id);
                var level = h.tagName.toLowerCase();
                toc.push({ id: id, text: text, level: level });
                sidebarHtml += '<a class="sidebar-link ' + level + '" data-target="' + id + '">' + text + '</a>';
            });

            sidebar.innerHTML = sidebarHtml;

            var links = sidebar.querySelectorAll('.sidebar-link');

            function onLinkClick(e) {
                var targetId = this.getAttribute('data-target');
                var target = document.getElementById(targetId);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }

            links.forEach(function(link) {
                link.addEventListener('click', onLinkClick);
            });

            function onScroll() {
                var scrollPos = window.scrollY + 100;
                var current = null;
                headings.forEach(function(h, i) {
                    var id = h.getAttribute('id');
                    if (!id) return;
                    if (h.offsetTop <= scrollPos) {
                        current = id;
                    }
                });
                links.forEach(function(link) {
                    if (link.getAttribute('data-target') === current) {
                        link.classList.add('active');
                    } else {
                        link.classList.remove('active');
                    }
                });
            }

            window.addEventListener('scroll', onScroll);
            onScroll();
        })();
    </script>
</body>
</html>