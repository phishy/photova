import express, { Request, Response } from 'express';
import cors from 'cors';
import type { Config } from '../config/schema.js';
import { OperationRouter } from '../router/index.js';
import type { OperationType } from '../operations/types.js';
import { generateOpenAPISpec } from '../openapi.js';

function getHomepageHtml(): string {
  return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Brighten API - AI Image Processing for Developers</title>
  <meta name="description" content="Transform images with AI. Background removal, photo restoration, colorization, and more. One API, unlimited possibilities.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      background: #09090b;
      color: #fafafa;
      line-height: 1.6;
      overflow-x: hidden;
    }
    
    .nav {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 100;
      padding: 16px 24px;
      background: rgba(9, 9, 11, 0.8);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .nav-inner {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .logo {
      font-weight: 800;
      font-size: 1.25rem;
      background: linear-gradient(135deg, #3b82f6, #8b5cf6);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .nav-links { display: flex; align-items: center; gap: 32px; }
    .nav-links a { color: #a1a1aa; text-decoration: none; font-size: 0.9rem; transition: color 0.2s; }
    .nav-links a:hover { color: #fff; }
    
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 12px 24px;
      font-size: 0.95rem;
      font-weight: 600;
      text-decoration: none;
      border-radius: 8px;
      transition: all 0.2s;
      cursor: pointer;
      border: none;
    }
    .btn-primary {
      background: linear-gradient(135deg, #3b82f6, #6366f1);
      color: #fff;
      box-shadow: 0 4px 24px rgba(99, 102, 241, 0.3);
    }
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 32px rgba(99, 102, 241, 0.4);
    }
    .btn-secondary {
      background: rgba(255,255,255,0.06);
      color: #fff;
      border: 1px solid rgba(255,255,255,0.1);
    }
    .btn-secondary:hover { background: rgba(255,255,255,0.1); }
    .btn-lg { padding: 16px 32px; font-size: 1.1rem; }
    
    .hero {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 120px 24px 80px;
      position: relative;
      overflow: hidden;
    }
    .hero::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle at 30% 20%, rgba(59, 130, 246, 0.15) 0%, transparent 40%),
                  radial-gradient(circle at 70% 60%, rgba(139, 92, 246, 0.1) 0%, transparent 40%);
      animation: pulse 8s ease-in-out infinite;
    }
    @keyframes pulse {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.8; transform: scale(1.05); }
    }
    .hero-content {
      position: relative;
      max-width: 900px;
      text-align: center;
    }
    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 16px;
      background: rgba(59, 130, 246, 0.1);
      border: 1px solid rgba(59, 130, 246, 0.2);
      border-radius: 100px;
      font-size: 0.85rem;
      color: #60a5fa;
      margin-bottom: 24px;
    }
    .hero h1 {
      font-size: clamp(2.5rem, 6vw, 4.5rem);
      font-weight: 800;
      line-height: 1.1;
      margin-bottom: 24px;
      letter-spacing: -0.02em;
    }
    .hero h1 span {
      background: linear-gradient(135deg, #3b82f6, #8b5cf6, #ec4899);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .hero-sub {
      font-size: 1.25rem;
      color: #a1a1aa;
      max-width: 600px;
      margin: 0 auto 40px;
    }
    .hero-ctas { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }
    .hero-stats {
      display: flex;
      justify-content: center;
      gap: 48px;
      margin-top: 64px;
      padding-top: 48px;
      border-top: 1px solid rgba(255,255,255,0.06);
    }
    .stat { text-align: center; }
    .stat-value { font-size: 2rem; font-weight: 700; color: #fff; }
    .stat-label { font-size: 0.85rem; color: #71717a; }
    
    section { padding: 120px 24px; }
    .container { max-width: 1200px; margin: 0 auto; }
    .section-label {
      font-size: 0.85rem;
      font-weight: 600;
      color: #3b82f6;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      margin-bottom: 12px;
    }
    .section-title {
      font-size: clamp(2rem, 4vw, 3rem);
      font-weight: 700;
      margin-bottom: 20px;
      letter-spacing: -0.02em;
    }
    .section-sub {
      font-size: 1.1rem;
      color: #a1a1aa;
      max-width: 600px;
    }
    
    .features { background: #0a0a0c; }
    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 24px;
      margin-top: 64px;
    }
    .feature-card {
      background: rgba(255,255,255,0.02);
      border: 1px solid rgba(255,255,255,0.06);
      border-radius: 16px;
      padding: 32px;
      transition: all 0.3s;
    }
    .feature-card:hover {
      background: rgba(255,255,255,0.04);
      border-color: rgba(59, 130, 246, 0.3);
      transform: translateY(-4px);
    }
    .feature-icon {
      width: 48px;
      height: 48px;
      background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(139, 92, 246, 0.2));
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      margin-bottom: 20px;
    }
    .feature-card h3 { font-size: 1.25rem; font-weight: 600; margin-bottom: 12px; }
    .feature-card p { color: #a1a1aa; font-size: 0.95rem; }
    
    .code-section { background: #09090b; }
    .code-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 48px; align-items: center; margin-top: 64px; }
    @media (max-width: 900px) { .code-grid { grid-template-columns: 1fr; } }
    .code-block {
      background: #0a0a0c;
      border: 1px solid rgba(255,255,255,0.06);
      border-radius: 12px;
      overflow: hidden;
    }
    .code-header {
      padding: 12px 16px;
      background: rgba(255,255,255,0.02);
      border-bottom: 1px solid rgba(255,255,255,0.06);
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .code-dot { width: 12px; height: 12px; border-radius: 50%; }
    .code-dot:nth-child(1) { background: #ef4444; }
    .code-dot:nth-child(2) { background: #eab308; }
    .code-dot:nth-child(3) { background: #22c55e; }
    .code-content {
      padding: 24px;
      font-family: 'JetBrains Mono', 'Fira Code', monospace;
      font-size: 0.85rem;
      line-height: 1.7;
      overflow-x: auto;
    }
    .code-content .comment { color: #6b7280; }
    .code-content .keyword { color: #c084fc; }
    .code-content .string { color: #4ade80; }
    .code-content .property { color: #60a5fa; }
    
    .pricing { background: #0a0a0c; }
    .pricing-header { text-align: center; margin-bottom: 64px; }
    .pricing-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
      gap: 24px;
      max-width: 900px;
      margin: 0 auto;
    }
    .price-card {
      background: rgba(255,255,255,0.02);
      border: 1px solid rgba(255,255,255,0.06);
      border-radius: 20px;
      padding: 40px;
      position: relative;
    }
    .price-card.featured {
      background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(139, 92, 246, 0.1));
      border-color: rgba(99, 102, 241, 0.3);
    }
    .price-card.featured::before {
      content: 'Most Popular';
      position: absolute;
      top: -12px;
      left: 50%;
      transform: translateX(-50%);
      padding: 6px 16px;
      background: linear-gradient(135deg, #3b82f6, #6366f1);
      border-radius: 100px;
      font-size: 0.75rem;
      font-weight: 600;
    }
    .price-name { font-size: 1.25rem; font-weight: 600; margin-bottom: 8px; }
    .price-desc { color: #71717a; font-size: 0.9rem; margin-bottom: 24px; }
    .price-amount { font-size: 3rem; font-weight: 800; margin-bottom: 8px; }
    .price-amount span { font-size: 1rem; font-weight: 400; color: #71717a; }
    .price-features { list-style: none; margin: 32px 0; }
    .price-features li {
      padding: 12px 0;
      border-bottom: 1px solid rgba(255,255,255,0.04);
      display: flex;
      align-items: center;
      gap: 12px;
      color: #d4d4d8;
      font-size: 0.95rem;
    }
    .price-features li::before { content: '✓'; color: #22c55e; font-weight: 600; }
    .price-card .btn { width: 100%; margin-top: 16px; }
    
    .cta-section {
      background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(139, 92, 246, 0.1));
      text-align: center;
      border-top: 1px solid rgba(255,255,255,0.06);
      border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .cta-section h2 { font-size: clamp(2rem, 4vw, 2.5rem); margin-bottom: 16px; }
    .cta-section p { color: #a1a1aa; margin-bottom: 32px; font-size: 1.1rem; }
    
    footer {
      padding: 48px 24px;
      text-align: center;
      color: #52525b;
      font-size: 0.9rem;
    }
    footer a { color: #71717a; text-decoration: none; }
    footer a:hover { color: #fff; }
    .footer-links { display: flex; justify-content: center; gap: 32px; margin-bottom: 24px; }
  </style>
</head>
<body>
  <nav class="nav">
    <div class="nav-inner">
      <div class="logo">Brighten API</div>
      <div class="nav-links">
        <a href="#features">Features</a>
        <a href="#pricing">Pricing</a>
        <a href="/docs">Docs</a>
        <a href="https://github.com/phishy/brighten" target="_blank">GitHub</a>
        <a href="#signup" class="btn btn-primary" style="padding: 8px 20px;">Start Free</a>
      </div>
    </div>
  </nav>

  <section class="hero">
    <div class="hero-content">
      <div class="hero-badge">
        <span>🚀</span> Now in Public Beta
      </div>
      <h1>Transform Images with <span>AI-Powered APIs</span></h1>
      <p class="hero-sub">
        Background removal, photo restoration, colorization, and object removal. 
        One simple API, production-ready in minutes.
      </p>
      <div class="hero-ctas">
        <a href="#signup" class="btn btn-primary btn-lg">Start Free Trial</a>
        <a href="/docs" class="btn btn-secondary btn-lg">View Documentation</a>
      </div>
      <div class="hero-stats">
        <div class="stat">
          <div class="stat-value">5</div>
          <div class="stat-label">AI Operations</div>
        </div>
        <div class="stat">
          <div class="stat-value">&lt;2s</div>
          <div class="stat-label">Avg Response</div>
        </div>
        <div class="stat">
          <div class="stat-value">99.9%</div>
          <div class="stat-label">Uptime SLA</div>
        </div>
      </div>
    </div>
  </section>

  <section class="features" id="features">
    <div class="container">
      <div class="section-label">Capabilities</div>
      <h2 class="section-title">Everything you need to process images</h2>
      <p class="section-sub">Production-ready AI endpoints for the most common image processing tasks.</p>
      
      <div class="features-grid">
        <div class="feature-card">
          <div class="feature-icon">✂️</div>
          <h3>Background Removal</h3>
          <p>Instantly remove backgrounds from any image with pixel-perfect edge detection. Perfect for product photos and portraits.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">🔮</div>
          <h3>Photo Restoration</h3>
          <p>Restore old, damaged, or faded photos. Fix scratches, tears, and discoloration automatically.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">🎨</div>
          <h3>Colorization</h3>
          <p>Breathe life into black and white photos with realistic, AI-generated colors.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">✨</div>
          <h3>Image Enhancement</h3>
          <p>Sharpen blurry images, enhance details, and improve overall quality with AI upscaling.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">🧹</div>
          <h3>Object Removal</h3>
          <p>Remove unwanted objects, people, or text from images. AI fills in the gaps seamlessly.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">⚡</div>
          <h3>Lightning Fast</h3>
          <p>Optimized infrastructure delivers results in seconds, not minutes. Built for production scale.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="code-section">
    <div class="container">
      <div class="code-grid">
        <div>
          <div class="section-label">Developer Experience</div>
          <h2 class="section-title">Simple API, powerful results</h2>
          <p class="section-sub" style="margin-bottom: 32px;">
            One endpoint format for all operations. Send an image, get a result. 
            No complex SDKs or dependencies required.
          </p>
          <a href="/docs" class="btn btn-secondary">Read the Docs →</a>
        </div>
        <div class="code-block">
          <div class="code-header">
            <span class="code-dot"></span>
            <span class="code-dot"></span>
            <span class="code-dot"></span>
          </div>
          <pre class="code-content"><span class="comment">// Remove background from an image</span>
<span class="keyword">const</span> response = <span class="keyword">await</span> fetch(<span class="string">'https://api.brighten.dev/api/v1/background-remove'</span>, {
  <span class="property">method</span>: <span class="string">'POST'</span>,
  <span class="property">headers</span>: {
    <span class="string">'Authorization'</span>: <span class="string">\`Bearer \${API_KEY}\`</span>,
    <span class="string">'Content-Type'</span>: <span class="string">'application/json'</span>
  },
  <span class="property">body</span>: JSON.stringify({
    <span class="property">image</span>: <span class="string">'data:image/png;base64,...'</span>
  })
});

<span class="keyword">const</span> { image, metadata } = <span class="keyword">await</span> response.json();
<span class="comment">// image: processed result as base64</span>
<span class="comment">// metadata: { provider, processingTime }</span></pre>
        </div>
      </div>
    </div>
  </section>

  <section class="pricing" id="pricing">
    <div class="container">
      <div class="pricing-header">
        <div class="section-label">Pricing</div>
        <h2 class="section-title">Start free, scale as you grow</h2>
        <p class="section-sub" style="margin: 0 auto;">No credit card required. Upgrade when you're ready.</p>
      </div>
      
      <div class="pricing-grid">
        <div class="price-card">
          <div class="price-name">Free</div>
          <div class="price-desc">For side projects and testing</div>
          <div class="price-amount">$0 <span>/ month</span></div>
          <ul class="price-features">
            <li>100 API calls / month</li>
            <li>All 5 operations</li>
            <li>Community support</li>
            <li>Watermarked output</li>
          </ul>
          <a href="#signup" class="btn btn-secondary">Get Started</a>
        </div>
        <div class="price-card featured">
          <div class="price-name">Pro</div>
          <div class="price-desc">For production applications</div>
          <div class="price-amount">$29 <span>/ month</span></div>
          <ul class="price-features">
            <li>10,000 API calls / month</li>
            <li>All 5 operations</li>
            <li>Priority support</li>
            <li>No watermarks</li>
            <li>Higher rate limits</li>
            <li>Usage analytics</li>
          </ul>
          <a href="#signup" class="btn btn-primary">Start Free Trial</a>
        </div>
      </div>
    </div>
  </section>

  <section class="cta-section" id="signup">
    <div class="container">
      <h2>Ready to transform your images?</h2>
      <p>Get your API key in seconds. No credit card required.</p>
      <a href="mailto:hello@brighten.dev?subject=API%20Access%20Request" class="btn btn-primary btn-lg">Request API Access</a>
    </div>
  </section>

  <footer>
    <div class="footer-links">
      <a href="/docs">Documentation</a>
      <a href="/api/openapi.json">OpenAPI Spec</a>
      <a href="https://github.com/phishy/brighten" target="_blank">GitHub</a>
    </div>
    <p>&copy; 2025 Brighten. Open source under BSL-1.1.</p>
  </footer>
</body>
</html>`;
}

function getDocsHtml(): string {
  return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Brighten API Documentation</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=JetBrains+Mono&display=swap" rel="stylesheet">
  <style>
    body { margin: 0; padding: 0; }
  </style>
</head>
<body>
  <redoc spec-url="/api/openapi.json" 
         hide-download-button="false"
         theme='{
           "colors": { "primary": { "main": "#3b82f6" } },
           "typography": { "fontFamily": "Inter, sans-serif", "code": { "fontFamily": "JetBrains Mono, monospace" } },
           "sidebar": { "backgroundColor": "#1a1a2e" }
         }'>
  </redoc>
  <script src="https://cdn.redoc.ly/redoc/latest/bundles/redoc.standalone.js"></script>
</body>
</html>`;
}

export function createServer(config: Config) {
  const app = express();
  const router = new OperationRouter(config);

  app.use(cors());
  app.use(express.json({ limit: '50mb' }));

  app.get('/', (_req: Request, res: Response) => {
    res.type('html').send(getHomepageHtml());
  });

  app.get('/docs', (_req: Request, res: Response) => {
    res.type('html').send(getDocsHtml());
  });

  app.get('/api/openapi.json', (_req: Request, res: Response) => {
    const openApiSpec = generateOpenAPISpec(config);
    res.json(openApiSpec);
  });

  app.get('/api/health', (_req: Request, res: Response) => {
    res.json({ status: 'ok' });
  });

  app.get('/api/operations', (_req: Request, res: Response) => {
    res.json({
      operations: router.getAvailableOperations().map(op => ({
        name: op,
        provider: router.getProviderForOperation(op),
      })),
    });
  });

  app.post('/api/v1/:operation', async (req: Request, res: Response) => {
    const { operation } = req.params;
    const { image, options } = req.body;

    if (!image) {
      res.status(400).json({ error: 'Image is required' });
      return;
    }

    const base64Match = image.match(/^data:([^;]+);base64,(.+)$/);
    let imageBuffer: Buffer;
    let mimeType: string;

    if (base64Match) {
      mimeType = base64Match[1];
      imageBuffer = Buffer.from(base64Match[2], 'base64');
    } else {
      mimeType = 'image/png';
      imageBuffer = Buffer.from(image, 'base64');
    }

    try {
      const result = await router.execute(operation as OperationType, {
        image: imageBuffer,
        mimeType,
        options,
      });

      const base64Result = result.image.toString('base64');

      res.json({
        image: `data:${result.mimeType};base64,${base64Result}`,
        metadata: result.metadata,
      });
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Unknown error';
      console.error(`Operation ${operation} failed:`, message);
      res.status(500).json({ error: message });
    }
  });

  return app;
}

export async function startServer(config: Config) {
  const app = createServer(config);
  const port = config.server.port;
  const host = config.server.host;

  return new Promise<typeof app>((resolve) => {
    app.listen(port, host, () => {
      console.log(`Brighten API running at http://${host}:${port}`);
      console.log(`API docs at http://${host}:${port}/docs`);
      console.log(`OpenAPI spec at http://${host}:${port}/api/openapi.json`);
      resolve(app);
    });
  });
}
