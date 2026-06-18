<?php
session_start();
require_once 'config.php';
include 'includes/header.php';
?>

<!-- 引入 MathJax 渲染 LaTeX 数学公式 -->
<script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
<script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">

<style>
    :root {
        --bg-base: #030305;
        --bg-panel: #0b0f17;
        --border-light: #1e293b;
        --text-main: #f8fafc;
        --text-muted: #94a3b8;
        --accent: #00f2fe;
        --accent-purple: #a855f7;
    }

    body { background-color: var(--bg-base); color: var(--text-main); font-family: 'Inter', sans-serif; line-height: 1.6; margin: 0; }
    
    .tech-hero {
        padding: 100px 20px 60px;
        text-align: center;
        background: radial-gradient(ellipse at top, rgba(0,242,254,0.1) 0%, transparent 60%);
        border-bottom: 1px solid var(--border-light);
    }
    .tech-hero h1 { font-size: 3.5rem; font-weight: 900; margin: 0 0 20px 0; letter-spacing: -1.5px; }
    .tech-hero p { color: var(--text-muted); font-size: 1.2rem; max-width: 700px; margin: 0 auto; }
    
    .container { max-width: 1000px; margin: 0 auto; padding: 60px 20px; }
    
    .algo-card {
        background: var(--bg-panel); border: 1px solid var(--border-light); border-radius: 16px;
        padding: 40px; margin-bottom: 40px; box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        position: relative; overflow: hidden;
    }
    
    .algo-card::before {
        content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%;
        background: var(--accent);
    }
    .algo-card:nth-child(even)::before { background: var(--accent-purple); }

    .algo-header { display: flex; align-items: center; gap: 15px; margin-bottom: 25px; }
    .algo-icon { width: 50px; height: 50px; background: rgba(255,255,255,0.05); border-radius: 12px; display: flex; justify-content: center; align-items: center; font-size: 1.5rem; color: var(--accent); }
    .algo-card:nth-child(even) .algo-icon { color: var(--accent-purple); }
    .algo-title { font-size: 1.8rem; font-weight: 800; margin: 0; color: #fff; }

    .algo-desc { font-size: 1.05rem; color: var(--text-muted); margin-bottom: 30px; }

    .math-box {
        background: #000; border: 1px dashed rgba(255,255,255,0.15); border-radius: 8px;
        padding: 25px; margin-bottom: 30px; overflow-x: auto;
    }

    .code-box {
        background: #000; border: 1px solid var(--border-light); border-radius: 8px;
        padding: 20px; font-family: 'JetBrains Mono', monospace; font-size: 0.9rem;
        color: #00e676; margin-bottom: 20px;
    }

    .dag-visualization {
        display: flex; justify-content: space-around; align-items: center;
        background: #000; border-radius: 12px; padding: 40px 20px; margin-bottom: 30px; border: 1px solid var(--border-light);
    }
    .dag-node { background: #1f2937; border: 1px solid var(--accent-purple); color: #fff; padding: 15px 25px; border-radius: 8px; font-weight: bold; position: relative; }
    .dag-edge { flex: 1; height: 2px; background: repeating-linear-gradient(to right, var(--accent-purple) 0, var(--accent-purple) 5px, transparent 5px, transparent 10px); position: relative; }
    .dag-edge::after { content: '➔'; position: absolute; right: -5px; top: -11px; color: var(--accent-purple); font-size: 1.2rem;}

</style>

<div class="tech-hero">
    <h1>Core Architecture & Algorithms</h1>
    <p>Dive into the mathematical models and graph theories powering the GridCitY Smart Builder Engine.</p>
</div>

<div class="container">

    <!-- 1. Cosine Similarity -->
    <div class="algo-card">
        <div class="algo-header">
            <div class="algo-icon"><i class="fas fa-fingerprint"></i></div>
            <h2 class="algo-title">Vector Space DNA Matching</h2>
        </div>
        <p class="algo-desc">
            Instead of basic conditional filtering, GridCitY assigns a 4-dimensional vector $V = [g, c, s, e]$ (Gamer, Creator, Student, Enthusiast) to both the <strong>User's Digital DNA</strong> and the <strong>Pre-built Packages</strong>. We calculate the exact mathematical alignment using Cosine Similarity to serve the ultimate recommendation.
        </p>
        
        <div class="math-box">
            $$ \text{Similarity} = \cos(\theta) = \frac{A \cdot B}{\|A\| \|B\|} = \frac{\sum_{i=1}^{n} A_i B_i}{\sqrt{\sum_{i=1}^{n} A_i^2} \sqrt{\sum_{i=1}^{n} B_i^2}} $$
        </div>

        <div class="code-box">
// Core Logic Implementation in packages.php<br>
function cosine_similarity($vec1, $vec2) {<br>
&nbsp;&nbsp;&nbsp;&nbsp;$dot_product = ($vec1['g'] * $vec2['g']) + ($vec1['c'] * $vec2['c']) + ...;<br>
&nbsp;&nbsp;&nbsp;&nbsp;$mag1 = sqrt(pow($vec1['g'], 2) + pow($vec1['c'], 2) + ...);<br>
&nbsp;&nbsp;&nbsp;&nbsp;return $dot_product / ($mag1 * $mag2);<br>
}
        </div>
    </div>

    <!-- 2. DAG Topology -->
    <div class="algo-card">
        <div class="algo-header">
            <div class="algo-icon"><i class="fas fa-project-diagram"></i></div>
            <h2 class="algo-title">DAG Topological Dependency</h2>
        </div>
        <p class="algo-desc">
            Hardware compatibility is strictly modeled as a <strong>Directed Acyclic Graph (DAG)</strong>. A node (e.g., Motherboard) cannot be unlocked until its parent node (e.g., CPU) verifies its socket criteria. This guarantees a mathematically impossible scenario for users to select incompatible parts.
        </p>

        <div class="dag-visualization">
            <div class="dag-node">CPU <br><span style="font-size:0.7rem; color:var(--text-muted);">Defines Socket</span></div>
            <div class="dag-edge"></div>
            <div class="dag-node">Motherboard <br><span style="font-size:0.7rem; color:var(--text-muted);">Verifies Socket</span></div>
            <div class="dag-edge"></div>
            <div class="dag-node">RAM / GPU <br><span style="font-size:0.7rem; color:var(--text-muted);">Verifies DDR/PCIe</span></div>
        </div>

        <div class="code-box">
// Dependency Map Definition<br>
$dependency_map = [<br>
&nbsp;&nbsp;&nbsp;&nbsp;1 => [2, 8],    // CPU -> Motherboard, Cooler<br>
&nbsp;&nbsp;&nbsp;&nbsp;2 => [3, 4],    // Motherboard -> RAM, GPU<br>
&nbsp;&nbsp;&nbsp;&nbsp;4 => [6]        // GPU -> PSU (Wattage Check)<br>
];
        </div>
    </div>

    <!-- 3. Non-linear Bottleneck -->
    <div class="algo-card">
        <div class="algo-header">
            <div class="algo-icon"><i class="fas fa-chart-line"></i></div>
            <h2 class="algo-title">Heuristic Bottleneck Calculus</h2>
        </div>
        <p class="algo-desc">
            Performance isn't linear. To predict multi-scenario benchmark scores (like Cyberpunk FPS or Premiere Pro rendering points), we use a fractional exponent formula to map real-time component prices into diminishing-return performance indices.
        </p>

        <div class="math-box">
            $$ \text{Performance Index} = \left( \frac{\text{Current Price}}{\text{Baseline Limit}} \right)^{0.6} \times 100 $$
            <br>
            $$ \text{FPS}_{\text{Cyberpunk}} = 30 + (\text{Index}_{\text{GPU}} \times 0.85) + (\text{Index}_{\text{CPU}} \times 0.15) $$
        </div>
        
        <p class="algo-desc" style="font-size: 0.9rem; margin-bottom: 0;">
            * The exponent <strong>0.6</strong> mathematically represents the real-world principle of diminishing returns in PC hardware (spending 2x the money does not yield 2x the FPS).
        </p>
    </div>

</div>

<?php include 'includes/footer.php'; ?>