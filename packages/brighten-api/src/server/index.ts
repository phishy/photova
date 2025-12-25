import express, { Request, Response } from 'express';
import cors from 'cors';
import type { Config } from '../config/schema.js';
import { OperationRouter } from '../router/index.js';
import type { OperationType } from '../operations/types.js';
import { generateOpenAPISpec } from '../openapi.js';
import { initPocketBase } from '../auth/client.js';
import { requireApiKey } from '../auth/middleware.js';
import { logUsage } from '../usage/service.js';
import { createAuthRoutes, createApiKeysRoutes, createUsageRoutes } from './routes.js';

function getHomepageHtml(): string {
  return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Brighten API – Image Processing Infrastructure</title>
  <meta name="description" content="The image processing API for modern applications. Background removal, restoration, colorization, and enhancement at scale.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #0b0c0e;
      --surface: #151619;
      --fg: #fff;
      --gray-400: #94a3b8;
      --gray-500: #71717a;
      --gray-600: #52525b;
      --primary: #3b82f6;
      --primary-glow: rgba(59, 130, 246, 0.25);
      --purple-glow: rgba(139, 92, 246, 0.2);
      --pink-glow: rgba(236, 72, 153, 0.15);
      --border: rgba(255,255,255,0.08);
      --border-hover: rgba(255,255,255,0.16);
    }
    
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }
    
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      background: var(--bg);
      color: var(--fg);
      line-height: 1.5;
      font-size: 16px;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }
    
    ::selection { background: rgba(59, 130, 246, 0.3); }
    
    .bg-gradient {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      height: 100vh;
      z-index: 0;
      overflow: hidden;
      pointer-events: none;
    }
    
    .bg-orb {
      position: absolute;
      border-radius: 50%;
      filter: blur(100px);
    }
    
    .bg-orb:nth-child(1) {
      width: 800px;
      height: 800px;
      background: var(--primary-glow);
      top: -300px;
      right: -200px;
      animation: float1 8s ease-in-out infinite;
    }
    
    .bg-orb:nth-child(2) {
      width: 600px;
      height: 600px;
      background: var(--purple-glow);
      bottom: 0;
      left: -200px;
      animation: float2 10s ease-in-out infinite;
    }
    
    .bg-orb:nth-child(3) {
      width: 500px;
      height: 500px;
      background: var(--pink-glow);
      top: 30%;
      left: 40%;
      animation: float3 12s ease-in-out infinite;
    }
    
    @keyframes float1 {
      0%, 100% { transform: translate(0, 0) scale(1); }
      50% { transform: translate(-80px, 60px) scale(1.1); }
    }
    
    @keyframes float2 {
      0%, 100% { transform: translate(0, 0) scale(1); }
      50% { transform: translate(100px, -80px) scale(1.15); }
    }
    
    @keyframes float3 {
      0%, 100% { transform: translate(0, 0) scale(1); }
      33% { transform: translate(60px, -40px) scale(1.05); }
      66% { transform: translate(-40px, 60px) scale(0.95); }
    }
    
    nav {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 100;
      padding: 0 24px;
      height: 64px;
      display: flex;
      align-items: center;
      background: rgba(11, 12, 14, 0.8);
      backdrop-filter: saturate(180%) blur(20px);
      border-bottom: 1px solid var(--border);
    }
    
    .nav-inner {
      max-width: 1200px;
      width: 100%;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    
    .logo {
      font-weight: 600;
      font-size: 15px;
      letter-spacing: -0.02em;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .logo-icon {
      color: var(--primary);
    }
    
    .nav-links {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .nav-links a {
      color: var(--gray-400);
      text-decoration: none;
      font-size: 14px;
      padding: 8px 16px;
      border-radius: 6px;
      transition: color 0.15s, background 0.15s;
    }
    
    .nav-links a:hover { color: var(--fg); }
    
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 10px 16px;
      font-size: 14px;
      font-weight: 500;
      text-decoration: none;
      border-radius: 8px;
      transition: all 0.15s;
      cursor: pointer;
      border: none;
      white-space: nowrap;
    }
    
    .btn-primary {
      background: var(--primary);
      color: var(--fg);
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }
    
    .btn-primary:hover {
      background: #60a5fa;
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
    }
    
    .btn-secondary {
      background: transparent;
      color: var(--fg);
      border: 1px solid var(--border);
    }
    
    .btn-secondary:hover {
      background: rgba(255,255,255,0.05);
      border-color: var(--border-hover);
    }
    
    .btn-lg { padding: 14px 28px; font-size: 15px; }
    
    .hero {
      position: relative;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 160px 24px 120px;
      text-align: center;
    }
    
    .hero-content {
      position: relative;
      z-index: 1;
      max-width: 800px;
    }
    
    .hero-announce {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      padding: 6px 6px 6px 16px;
      background: var(--gray-950);
      border: 1px solid var(--border);
      border-radius: 100px;
      font-size: 13px;
      color: var(--gray-400);
      margin-bottom: 32px;
    }
    
    .hero-announce-tag {
      padding: 4px 10px;
      background: var(--fg);
      color: var(--bg);
      border-radius: 100px;
      font-size: 12px;
      font-weight: 500;
    }
    
    .hero h1 {
      font-size: clamp(40px, 8vw, 72px);
      font-weight: 700;
      line-height: 1.05;
      letter-spacing: -0.03em;
      margin-bottom: 24px;
    }
    
    .hero h1 {
      background: linear-gradient(to bottom, #fff 0%, #94a3b8 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    
    .hero h1 .gradient {
      background: linear-gradient(135deg, var(--primary) 0%, #a78bfa 50%, #ec4899 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    
    .hero-sub {
      font-size: 18px;
      color: var(--gray-400);
      max-width: 520px;
      margin: 0 auto 40px;
      line-height: 1.6;
    }
    
    .hero-ctas {
      display: flex;
      gap: 12px;
      justify-content: center;
      flex-wrap: wrap;
    }
    
    .hero-visual {
      position: relative;
      z-index: 1;
      margin-top: 80px;
      width: 100%;
      max-width: 900px;
    }
    
    .terminal {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 16px;
      overflow: hidden;
      text-align: left;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }
    
    .terminal-header {
      padding: 12px 16px;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .terminal-dot {
      width: 12px;
      height: 12px;
      border-radius: 50%;
    }
    
    .terminal-dot:nth-child(1) { background: #ef4444; }
    .terminal-dot:nth-child(2) { background: #eab308; }
    .terminal-dot:nth-child(3) { background: #22c55e; }
    
    .terminal-title {
      flex: 1;
      text-align: center;
      font-size: 13px;
      color: var(--gray-500);
    }
    
    .terminal-body {
      padding: 24px;
      font-family: 'JetBrains Mono', monospace;
      font-size: 13px;
      line-height: 1.8;
      color: var(--gray-400);
      overflow-x: auto;
    }
    
    .terminal-body .prompt { color: var(--gray-500); }
    .terminal-body .cmd { color: var(--fg); }
    .terminal-body .str { color: #a5d6ff; }
    .terminal-body .comment { color: var(--gray-600); }
    .terminal-body .key { color: #7ee787; }
    
    section {
      position: relative;
      z-index: 1;
      padding: 120px 24px;
    }
    
    .container { max-width: 1200px; margin: 0 auto; }
    
    .section-header {
      text-align: center;
      max-width: 600px;
      margin: 0 auto 64px;
    }
    
    .section-header h2 {
      font-size: clamp(32px, 5vw, 48px);
      font-weight: 700;
      letter-spacing: -0.03em;
      margin-bottom: 16px;
    }
    
    .section-header p {
      font-size: 17px;
      color: var(--gray-400);
      line-height: 1.6;
    }
    
    .features-section { border-top: 1px solid var(--border); }
    
    .features-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 24px;
    }
    
    @media (max-width: 900px) {
      .features-grid { grid-template-columns: 1fr; }
    }
    
    .feature {
      background: linear-gradient(180deg, var(--surface) 0%, rgba(21, 22, 25, 0.4) 100%);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 32px;
      transition: all 0.2s;
    }
    
    .feature:hover { 
      border-color: var(--border-hover);
      transform: translateY(-4px);
    }
    
    .feature-icon {
      width: 48px;
      height: 48px;
      background: rgba(59, 130, 246, 0.1);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 20px;
      font-size: 20px;
      color: var(--primary);
    }
    
    .feature h3 {
      font-size: 16px;
      font-weight: 600;
      margin-bottom: 8px;
      letter-spacing: -0.01em;
    }
    
    .feature p {
      font-size: 14px;
      color: var(--gray-400);
      line-height: 1.6;
    }
    
    .code-section { border-top: 1px solid var(--border); }
    
    .code-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 64px;
      align-items: center;
    }
    
    @media (max-width: 900px) {
      .code-grid { grid-template-columns: 1fr; gap: 48px; }
    }
    
    .code-content h2 {
      font-size: clamp(28px, 4vw, 40px);
      font-weight: 700;
      letter-spacing: -0.03em;
      margin-bottom: 16px;
    }
    
    .code-content p {
      font-size: 16px;
      color: var(--gray-400);
      line-height: 1.7;
      margin-bottom: 32px;
    }
    
    .pricing-section { border-top: 1px solid var(--border); }
    
    .pricing-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 24px;
      max-width: 800px;
      margin: 0 auto;
    }
    
    @media (max-width: 700px) {
      .pricing-grid { grid-template-columns: 1fr; }
    }
    
    .price-card {
      background: linear-gradient(180deg, var(--surface) 0%, rgba(21, 22, 25, 0.4) 100%);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 40px;
      position: relative;
      transition: all 0.2s;
    }
    
    .price-card:hover {
      border-color: var(--border-hover);
    }
    
    .price-card.featured {
      border-color: var(--primary);
      box-shadow: 0 0 0 1px var(--primary), 0 25px 50px -12px rgba(59, 130, 246, 0.15);
    }
    
    .price-badge {
      position: absolute;
      top: 16px;
      right: 16px;
      padding: 4px 10px;
      background: var(--primary);
      color: var(--fg);
      border-radius: 100px;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    
    .price-name {
      font-size: 14px;
      font-weight: 500;
      color: var(--gray-400);
      margin-bottom: 8px;
    }
    
    .price-amount {
      font-size: 48px;
      font-weight: 700;
      letter-spacing: -0.03em;
      margin-bottom: 4px;
    }
    
    .price-period {
      font-size: 14px;
      color: var(--gray-500);
      margin-bottom: 24px;
    }
    
    .price-features {
      list-style: none;
      margin-bottom: 32px;
    }
    
    .price-features li {
      padding: 10px 0;
      font-size: 14px;
      color: var(--gray-400);
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .price-features li::before {
      content: '';
      width: 16px;
      height: 16px;
      background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%2322c55e'%3E%3Cpath d='M13.78 4.22a.75.75 0 010 1.06l-7.25 7.25a.75.75 0 01-1.06 0L2.22 9.28a.75.75 0 011.06-1.06L6 10.94l6.72-6.72a.75.75 0 011.06 0z'/%3E%3C/svg%3E") no-repeat center;
      flex-shrink: 0;
    }
    
    .price-card .btn { width: 100%; }
    
    .cta-section {
      border-top: 1px solid var(--border);
      text-align: center;
    }
    
    .cta-section h2 {
      font-size: clamp(32px, 5vw, 48px);
      font-weight: 700;
      letter-spacing: -0.03em;
      margin-bottom: 16px;
    }
    
    .cta-section p {
      font-size: 17px;
      color: var(--gray-400);
      margin-bottom: 32px;
    }
    
    footer {
      position: relative;
      z-index: 1;
      padding: 40px 24px;
      border-top: 1px solid var(--border);
    }
    
    .footer-inner {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    
    .footer-links {
      display: flex;
      gap: 24px;
    }
    
    .footer-links a {
      color: var(--gray-500);
      text-decoration: none;
      font-size: 14px;
      transition: color 0.15s;
    }
    
    .footer-links a:hover { color: var(--fg); }
    
    .footer-copy {
      font-size: 14px;
      color: var(--gray-600);
    }
    
    @media (max-width: 600px) {
      .footer-inner { flex-direction: column; gap: 16px; }
      .nav-links a:not(.btn) { display: none; }
    }
  </style>
</head>
<body>
  <div class="bg-gradient">
    <div class="bg-orb"></div>
    <div class="bg-orb"></div>
    <div class="bg-orb"></div>
  </div>
  
  <nav>
    <div class="nav-inner">
      <div class="logo">
        <svg class="logo-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="5"/>
          <path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/>
        </svg>
        Brighten <span style="color: var(--gray-500); font-weight: 400;">API</span>
      </div>
      <div class="nav-links">
        <a href="#features">Features</a>
        <a href="#pricing">Pricing</a>
        <a href="/docs">Docs</a>
        <a href="https://github.com/phishy/brighten" target="_blank">GitHub</a>
        <a href="#signup" class="btn btn-primary">Get Started</a>
      </div>
    </div>
  </nav>

  <section class="hero">
    <div class="hero-content">
      <div class="hero-announce">
        Introducing Brighten API
        <span class="hero-announce-tag">Beta</span>
      </div>
      <h1>Image processing<br><span class="gradient">infrastructure</span></h1>
      <p class="hero-sub">
        The API for AI-powered image processing. Background removal, restoration, 
        colorization, and enhancement—production-ready at any scale.
      </p>
      <div class="hero-ctas">
        <a href="#signup" class="btn btn-primary btn-lg">Start for Free</a>
        <a href="/docs" class="btn btn-secondary btn-lg">Documentation</a>
      </div>
    </div>
    
    <div class="hero-visual">
      <div class="terminal">
        <div class="terminal-header">
          <span class="terminal-dot"></span>
          <span class="terminal-dot"></span>
          <span class="terminal-dot"></span>
          <span class="terminal-title">terminal</span>
        </div>
        <div class="terminal-body">
<span class="comment"># Remove background from an image</span>
<span class="prompt">$</span> <span class="cmd">curl</span> <span class="str">https://api.brighten.dev/api/v1/background-remove</span> \\
    <span class="cmd">-H</span> <span class="str">"Authorization: Bearer \$API_KEY"</span> \\
    <span class="cmd">-d</span> <span class="str">'{"image": "data:image/png;base64,..."}'</span>

<span class="comment"># Response</span>
{
  <span class="key">"image"</span>: <span class="str">"data:image/png;base64,..."</span>,
  <span class="key">"metadata"</span>: { <span class="key">"processingTime"</span>: <span class="str">1240</span> }
}</div>
      </div>
    </div>
  </section>

  <section class="features-section" id="features">
    <div class="container">
      <div class="section-header">
        <h2>Built for developers</h2>
        <p>Production-ready AI endpoints with a simple, unified interface.</p>
      </div>
      
      <div class="features-grid">
        <div class="feature">
          <div class="feature-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M5 3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5z"/>
              <path d="M9 3v18"/>
            </svg>
          </div>
          <h3>Background Removal</h3>
          <p>Pixel-perfect edge detection for product photos, portraits, and complex scenes.</p>
        </div>
        <div class="feature">
          <div class="feature-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 2a10 10 0 1 0 10 10"/>
              <path d="M12 12l4-4"/>
              <path d="M16 8h-4v4"/>
            </svg>
          </div>
          <h3>Photo Restoration</h3>
          <p>Automatically fix scratches, tears, and damage in old or degraded photos.</p>
        </div>
        <div class="feature">
          <div class="feature-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <line x1="14.31" y1="8" x2="20.05" y2="17.94"/>
              <line x1="9.69" y1="8" x2="21.17" y2="8"/>
              <line x1="7.38" y1="12" x2="13.12" y2="2.06"/>
            </svg>
          </div>
          <h3>Colorization</h3>
          <p>Add realistic color to black and white images using deep learning models.</p>
        </div>
        <div class="feature">
          <div class="feature-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polygon points="12 2 2 7 12 12 22 7 12 2"/>
              <polyline points="2 17 12 22 22 17"/>
              <polyline points="2 12 12 17 22 12"/>
            </svg>
          </div>
          <h3>Enhancement</h3>
          <p>Upscale, sharpen, and improve image quality with AI-powered processing.</p>
        </div>
        <div class="feature">
          <div class="feature-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="3" width="18" height="18" rx="2"/>
              <path d="M9 9h.01"/>
              <path d="M15 15h.01"/>
              <path d="M9 15l6-6"/>
            </svg>
          </div>
          <h3>Object Removal</h3>
          <p>Seamlessly remove unwanted objects with intelligent inpainting.</p>
        </div>
        <div class="feature">
          <div class="feature-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 2v4"/>
              <path d="M12 18v4"/>
              <path d="M4.93 4.93l2.83 2.83"/>
              <path d="M16.24 16.24l2.83 2.83"/>
              <path d="M2 12h4"/>
              <path d="M18 12h4"/>
              <path d="M4.93 19.07l2.83-2.83"/>
              <path d="M16.24 7.76l2.83-2.83"/>
            </svg>
          </div>
          <h3>Sub-second Latency</h3>
          <p>Optimized infrastructure for production workloads at any scale.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="code-section">
    <div class="container">
      <div class="code-grid">
        <div class="code-content">
          <h2>One API for everything</h2>
          <p>
            Simple, consistent endpoints for all operations. No SDKs required—just 
            send a request and get your processed image back in seconds.
          </p>
          <a href="/docs" class="btn btn-secondary">Read the docs →</a>
        </div>
        <div class="terminal">
          <div class="terminal-header">
            <span class="terminal-dot"></span>
            <span class="terminal-dot"></span>
            <span class="terminal-dot"></span>
            <span class="terminal-title">api.ts</span>
          </div>
          <pre class="terminal-body"><span class="key">const</span> response = <span class="key">await</span> fetch(
  <span class="str">'https://api.brighten.dev/api/v1/colorize'</span>,
  {
    <span class="key">method</span>: <span class="str">'POST'</span>,
    <span class="key">headers</span>: {
      <span class="str">'Authorization'</span>: <span class="str">\`Bearer \${key}\`</span>,
      <span class="str">'Content-Type'</span>: <span class="str">'application/json'</span>,
    },
    <span class="key">body</span>: JSON.stringify({ <span class="key">image</span>: base64 }),
  }
);

<span class="key">const</span> { image } = <span class="key">await</span> response.json();</pre>
        </div>
      </div>
    </div>
  </section>

  <section class="pricing-section" id="pricing">
    <div class="container">
      <div class="section-header">
        <h2>Simple pricing</h2>
        <p>Start free, then pay as you grow. No surprises.</p>
      </div>
      
      <div class="pricing-grid">
        <div class="price-card">
          <div class="price-name">Free</div>
          <div class="price-amount">$0</div>
          <div class="price-period">100 requests/month</div>
          <ul class="price-features">
            <li>All 5 operations</li>
            <li>Community support</li>
            <li>Standard rate limits</li>
          </ul>
          <a href="#signup" class="btn btn-secondary">Get Started</a>
        </div>
        <div class="price-card featured">
          <div class="price-badge">Popular</div>
          <div class="price-name">Pro</div>
          <div class="price-amount">$29</div>
          <div class="price-period">10,000 requests/month</div>
          <ul class="price-features">
            <li>All 5 operations</li>
            <li>Priority support</li>
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
      <h2>Start building today</h2>
      <p>Get your API key in seconds. No credit card required.</p>
      <a href="mailto:hello@brighten.dev?subject=API%20Access%20Request" class="btn btn-primary btn-lg">Request Access</a>
    </div>
  </section>

  <footer>
    <div class="footer-inner">
      <div class="footer-links">
        <a href="/docs">Documentation</a>
        <a href="/api/openapi.json">OpenAPI</a>
        <a href="https://github.com/phishy/brighten" target="_blank">GitHub</a>
      </div>
      <div class="footer-copy">© 2025 Brighten</div>
    </div>
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

function getDashboardHtml(): string {
  return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Brighten API</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <style>
    :root {
      --bg: #0b0c0e;
      --surface: #151619;
      --surface-hover: #1c1d21;
      --fg: #fff;
      --gray-400: #94a3b8;
      --gray-500: #71717a;
      --gray-600: #52525b;
      --primary: #3b82f6;
      --primary-hover: #60a5fa;
      --success: #22c55e;
      --error: #ef4444;
      --border: rgba(255,255,255,0.08);
      --border-hover: rgba(255,255,255,0.16);
    }
    
    * { box-sizing: border-box; margin: 0; padding: 0; }
    
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      background: var(--bg);
      color: var(--fg);
      line-height: 1.5;
      min-height: 100vh;
    }
    
    .app { display: flex; min-height: 100vh; }
    
    .sidebar {
      width: 240px;
      background: var(--surface);
      border-right: 1px solid var(--border);
      padding: 24px 16px;
      display: flex;
      flex-direction: column;
    }
    
    .logo {
      font-weight: 600;
      font-size: 16px;
      padding: 0 8px 24px;
      border-bottom: 1px solid var(--border);
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .logo svg { color: var(--primary); }
    
    .nav-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 12px;
      border-radius: 8px;
      color: var(--gray-400);
      text-decoration: none;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.15s;
      margin-bottom: 4px;
    }
    
    .nav-item:hover { background: var(--surface-hover); color: var(--fg); }
    .nav-item.active { background: rgba(59, 130, 246, 0.1); color: var(--primary); }
    
    .nav-item svg { width: 18px; height: 18px; opacity: 0.7; }
    
    .sidebar-footer { margin-top: auto; padding-top: 24px; border-top: 1px solid var(--border); }
    
    .main { flex: 1; padding: 32px 48px; overflow-y: auto; }
    
    .page-header {
      margin-bottom: 32px;
    }
    
    .page-header h1 { font-size: 24px; font-weight: 600; letter-spacing: -0.02em; }
    .page-header p { color: var(--gray-400); margin-top: 4px; }
    
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
      margin-bottom: 32px;
    }
    
    .stat-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 20px;
    }
    
    .stat-label { font-size: 13px; color: var(--gray-400); margin-bottom: 8px; }
    .stat-value { font-size: 28px; font-weight: 600; letter-spacing: -0.02em; }
    .stat-sub { font-size: 12px; color: var(--gray-500); margin-top: 4px; }
    
    .card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      margin-bottom: 24px;
    }
    
    .card-header {
      padding: 16px 20px;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    
    .card-title { font-size: 14px; font-weight: 600; }
    .card-body { padding: 20px; }
    
    .chart-container { height: 280px; position: relative; }
    
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      padding: 8px 14px;
      font-size: 13px;
      font-weight: 500;
      border-radius: 6px;
      border: none;
      cursor: pointer;
      transition: all 0.15s;
    }
    
    .btn-primary { background: var(--primary); color: var(--fg); }
    .btn-primary:hover { background: var(--primary-hover); }
    
    .btn-secondary { background: transparent; color: var(--fg); border: 1px solid var(--border); }
    .btn-secondary:hover { background: var(--surface-hover); border-color: var(--border-hover); }
    
    .btn-danger { background: transparent; color: var(--error); border: 1px solid var(--error); }
    .btn-danger:hover { background: rgba(239, 68, 68, 0.1); }
    
    .keys-list { display: flex; flex-direction: column; gap: 12px; }
    
    .key-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px;
      background: var(--bg);
      border-radius: 8px;
    }
    
    .key-info { flex: 1; }
    .key-name { font-weight: 500; margin-bottom: 4px; }
    .key-prefix { font-family: 'JetBrains Mono', monospace; font-size: 13px; color: var(--gray-400); }
    .key-meta { font-size: 12px; color: var(--gray-500); margin-top: 4px; }
    .key-actions { display: flex; gap: 8px; }
    
    .status-badge {
      display: inline-flex;
      align-items: center;
      padding: 2px 8px;
      font-size: 11px;
      font-weight: 500;
      border-radius: 100px;
      text-transform: uppercase;
    }
    
    .status-active { background: rgba(34, 197, 94, 0.1); color: var(--success); }
    .status-revoked { background: rgba(239, 68, 68, 0.1); color: var(--error); }
    
    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 6px; }
    
    .form-input {
      width: 100%;
      padding: 10px 12px;
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 6px;
      color: var(--fg);
      font-size: 14px;
    }
    
    .form-input:focus { outline: none; border-color: var(--primary); }
    
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.7);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 100;
    }
    
    .modal-overlay.show { display: flex; }
    
    .modal {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      width: 100%;
      max-width: 440px;
      padding: 24px;
    }
    
    .modal-header { font-size: 16px; font-weight: 600; margin-bottom: 16px; }
    .modal-footer { display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px; }
    
    .new-key-display {
      background: var(--bg);
      border: 1px solid var(--success);
      border-radius: 8px;
      padding: 16px;
      margin: 16px 0;
    }
    
    .new-key-display code {
      font-family: 'JetBrains Mono', monospace;
      font-size: 13px;
      word-break: break-all;
    }
    
    .new-key-warning {
      font-size: 12px;
      color: var(--gray-400);
      margin-top: 8px;
    }
    
    .usage-table { width: 100%; border-collapse: collapse; }
    .usage-table th { text-align: left; font-size: 12px; font-weight: 500; color: var(--gray-400); padding: 12px 16px; border-bottom: 1px solid var(--border); }
    .usage-table td { padding: 12px 16px; border-bottom: 1px solid var(--border); font-size: 14px; }
    .usage-table tr:hover { background: var(--surface-hover); }
    
    .auth-page {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
    }
    
    .auth-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 40px;
      width: 100%;
      max-width: 400px;
    }
    
    .auth-header { text-align: center; margin-bottom: 32px; }
    .auth-header h1 { font-size: 24px; font-weight: 600; margin-bottom: 8px; }
    .auth-header p { color: var(--gray-400); font-size: 14px; }
    
    .auth-footer { text-align: center; margin-top: 24px; font-size: 14px; color: var(--gray-400); }
    .auth-footer a { color: var(--primary); text-decoration: none; }
    .auth-footer a:hover { text-decoration: underline; }
    
    .error-message {
      background: rgba(239, 68, 68, 0.1);
      border: 1px solid var(--error);
      border-radius: 8px;
      padding: 12px;
      margin-bottom: 16px;
      font-size: 13px;
      color: var(--error);
      display: none;
    }
    
    .error-message.show { display: block; }
    
    .hidden { display: none !important; }
    
    @media (max-width: 1024px) {
      .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }
    
    @media (max-width: 768px) {
      .sidebar { display: none; }
      .main { padding: 24px; }
      .stats-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <div id="auth-page" class="auth-page">
    <div class="auth-card">
      <div class="auth-header">
        <h1>Brighten API</h1>
        <p id="auth-subtitle">Sign in to your account</p>
      </div>
      <div id="auth-error" class="error-message"></div>
      <form id="auth-form">
        <div id="name-group" class="form-group hidden">
          <label class="form-label">Name</label>
          <input type="text" id="name" class="form-input" placeholder="Your name">
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" id="email" class="form-input" placeholder="you@example.com" required>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" id="password" class="form-input" placeholder="Enter your password" required>
        </div>
        <button type="submit" id="auth-submit" class="btn btn-primary" style="width: 100%; margin-top: 8px;">Sign In</button>
      </form>
      <div class="auth-footer">
        <span id="auth-toggle-text">Don't have an account?</span>
        <a href="#" id="auth-toggle">Sign up</a>
      </div>
    </div>
  </div>

  <div id="app" class="app hidden">
    <aside class="sidebar">
      <div class="logo">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="5"/>
          <path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/>
        </svg>
        Brighten
      </div>
      <nav>
        <a class="nav-item active" data-page="overview">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
          Overview
        </a>
        <a class="nav-item" data-page="keys">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
          API Keys
        </a>
        <a class="nav-item" data-page="usage">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
          Usage
        </a>
      </nav>
      <div class="sidebar-footer">
        <a class="nav-item" href="/docs" target="_blank">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          Documentation
        </a>
        <a class="nav-item" id="logout-btn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          Sign Out
        </a>
      </div>
    </aside>

    <main class="main">
      <section id="page-overview">
        <div class="page-header">
          <h1>Overview</h1>
          <p>Welcome back! Here's your API usage summary.</p>
        </div>
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-label">Requests This Month</div>
            <div class="stat-value" id="stat-requests">-</div>
            <div class="stat-sub" id="stat-limit">of - limit</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Success Rate</div>
            <div class="stat-value" id="stat-success">-</div>
            <div class="stat-sub">last 30 days</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Avg Latency</div>
            <div class="stat-value" id="stat-latency">-</div>
            <div class="stat-sub">milliseconds</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Active Keys</div>
            <div class="stat-value" id="stat-keys">-</div>
            <div class="stat-sub">API keys</div>
          </div>
        </div>
        <div class="card">
          <div class="card-header">
            <span class="card-title">Requests (Last 30 Days)</span>
          </div>
          <div class="card-body">
            <div class="chart-container">
              <canvas id="requests-chart"></canvas>
            </div>
          </div>
        </div>
      </section>

      <section id="page-keys" class="hidden">
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
          <div>
            <h1>API Keys</h1>
            <p>Manage your API keys for accessing the Brighten API.</p>
          </div>
          <button class="btn btn-primary" id="create-key-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Create Key
          </button>
        </div>
        <div class="card">
          <div class="card-body">
            <div class="keys-list" id="keys-list">
              <div style="text-align: center; color: var(--gray-500); padding: 24px;">Loading...</div>
            </div>
          </div>
        </div>
      </section>

      <section id="page-usage" class="hidden">
        <div class="page-header">
          <h1>Usage</h1>
          <p>Detailed breakdown of your API usage by operation.</p>
        </div>
        <div class="card">
          <div class="card-header">
            <span class="card-title">Usage by Operation</span>
          </div>
          <div class="card-body" style="padding: 0;">
            <table class="usage-table">
              <thead>
                <tr>
                  <th>Operation</th>
                  <th>Requests</th>
                  <th>Errors</th>
                  <th>Avg Latency</th>
                </tr>
              </thead>
              <tbody id="usage-table-body">
                <tr><td colspan="4" style="text-align: center; color: var(--gray-500); padding: 24px;">Loading...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </main>
  </div>

  <div class="modal-overlay" id="create-key-modal">
    <div class="modal">
      <div class="modal-header">Create API Key</div>
      <form id="create-key-form">
        <div class="form-group">
          <label class="form-label">Key Name</label>
          <input type="text" id="key-name" class="form-input" placeholder="e.g., Production" required>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" id="cancel-create-key">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Key</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal-overlay" id="new-key-modal">
    <div class="modal">
      <div class="modal-header">API Key Created</div>
      <div class="new-key-display">
        <code id="new-key-value"></code>
      </div>
      <div class="new-key-warning">
        Make sure to copy your API key now. You won't be able to see it again!
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" id="copy-key-btn">Copy</button>
        <button type="button" class="btn btn-primary" id="close-new-key-modal">Done</button>
      </div>
    </div>
  </div>

  <script>
    const API_BASE = '/api';
    let token = localStorage.getItem('token');
    let currentUser = null;
    let requestsChart = null;

    async function api(path, options = {}) {
      const headers = { 'Content-Type': 'application/json', ...options.headers };
      if (token) headers['Authorization'] = 'Bearer ' + token;
      const res = await fetch(API_BASE + path, { ...options, headers });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Request failed');
      return data;
    }

    function showPage(name) {
      document.querySelectorAll('[id^="page-"]').forEach(p => p.classList.add('hidden'));
      document.getElementById('page-' + name).classList.remove('hidden');
      document.querySelectorAll('.nav-item[data-page]').forEach(n => n.classList.remove('active'));
      document.querySelector('[data-page="' + name + '"]').classList.add('active');
      
      if (name === 'overview') loadOverview();
      if (name === 'keys') loadKeys();
      if (name === 'usage') loadUsage();
    }

    async function loadOverview() {
      try {
        const [current, summary, timeseries, keys] = await Promise.all([
          api('/usage/current'),
          api('/usage/summary'),
          api('/usage/timeseries?metric=requests'),
          api('/keys')
        ]);

        document.getElementById('stat-requests').textContent = current.used.toLocaleString();
        document.getElementById('stat-limit').textContent = 'of ' + current.limit.toLocaleString() + ' limit';
        
        const successRate = summary.totalRequests > 0 
          ? Math.round(((summary.totalRequests - summary.totalErrors) / summary.totalRequests) * 100) 
          : 100;
        document.getElementById('stat-success').textContent = successRate + '%';
        document.getElementById('stat-latency').textContent = summary.avgLatencyMs || '-';
        document.getElementById('stat-keys').textContent = keys.keys.filter(k => k.status === 'active').length;

        if (requestsChart) requestsChart.destroy();
        const ctx = document.getElementById('requests-chart').getContext('2d');
        requestsChart = new Chart(ctx, {
          type: 'line',
          data: {
            labels: timeseries.data.map(d => d.timestamp.slice(5)),
            datasets: [{
              label: 'Requests',
              data: timeseries.data.map(d => d.value),
              borderColor: '#3b82f6',
              backgroundColor: 'rgba(59, 130, 246, 0.1)',
              fill: true,
              tension: 0.3,
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
              x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#71717a' } },
              y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#71717a' }, beginAtZero: true }
            }
          }
        });
      } catch (err) {
        console.error('Failed to load overview:', err);
      }
    }

    async function loadKeys() {
      try {
        const { keys } = await api('/keys');
        const list = document.getElementById('keys-list');
        
        if (keys.length === 0) {
          list.innerHTML = '<div style="text-align: center; color: var(--gray-500); padding: 24px;">No API keys yet. Create one to get started.</div>';
          return;
        }

        list.innerHTML = keys.map(k => \`
          <div class="key-item">
            <div class="key-info">
              <div class="key-name">\${k.name} <span class="status-badge status-\${k.status}">\${k.status}</span></div>
              <div class="key-prefix">\${k.prefix}</div>
              <div class="key-meta">Created \${new Date(k.created).toLocaleDateString()}\${k.lastUsedAt ? ' · Last used ' + new Date(k.lastUsedAt).toLocaleDateString() : ''}</div>
            </div>
            <div class="key-actions">
              \${k.status === 'active' ? '<button class="btn btn-danger" onclick="revokeKey(\\'' + k.id + '\\')">Revoke</button>' : ''}
            </div>
          </div>
        \`).join('');
      } catch (err) {
        console.error('Failed to load keys:', err);
      }
    }

    async function loadUsage() {
      try {
        const summary = await api('/usage/summary');
        const tbody = document.getElementById('usage-table-body');
        const ops = Object.entries(summary.byOperation);
        
        if (ops.length === 0) {
          tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: var(--gray-500); padding: 24px;">No usage data yet.</td></tr>';
          return;
        }

        tbody.innerHTML = ops.map(([op, stats]) => \`
          <tr>
            <td>\${op}</td>
            <td>\${stats.requests.toLocaleString()}</td>
            <td>\${stats.errors.toLocaleString()}</td>
            <td>\${stats.avgLatencyMs}ms</td>
          </tr>
        \`).join('');
      } catch (err) {
        console.error('Failed to load usage:', err);
      }
    }

    async function revokeKey(id) {
      if (!confirm('Are you sure you want to revoke this key? This cannot be undone.')) return;
      try {
        await api('/keys/' + id, { method: 'PATCH', body: JSON.stringify({ status: 'revoked' }) });
        loadKeys();
      } catch (err) {
        alert('Failed to revoke key: ' + err.message);
      }
    }

    document.querySelectorAll('.nav-item[data-page]').forEach(item => {
      item.addEventListener('click', () => showPage(item.dataset.page));
    });

    document.getElementById('create-key-btn').addEventListener('click', () => {
      document.getElementById('create-key-modal').classList.add('show');
    });

    document.getElementById('cancel-create-key').addEventListener('click', () => {
      document.getElementById('create-key-modal').classList.remove('show');
    });

    document.getElementById('create-key-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const name = document.getElementById('key-name').value;
      try {
        const result = await api('/keys', { method: 'POST', body: JSON.stringify({ name }) });
        document.getElementById('create-key-modal').classList.remove('show');
        document.getElementById('new-key-value').textContent = result.key;
        document.getElementById('new-key-modal').classList.add('show');
        document.getElementById('key-name').value = '';
      } catch (err) {
        alert('Failed to create key: ' + err.message);
      }
    });

    document.getElementById('copy-key-btn').addEventListener('click', () => {
      navigator.clipboard.writeText(document.getElementById('new-key-value').textContent);
      document.getElementById('copy-key-btn').textContent = 'Copied!';
      setTimeout(() => document.getElementById('copy-key-btn').textContent = 'Copy', 2000);
    });

    document.getElementById('close-new-key-modal').addEventListener('click', () => {
      document.getElementById('new-key-modal').classList.remove('show');
      loadKeys();
    });

    document.getElementById('logout-btn').addEventListener('click', () => {
      localStorage.removeItem('token');
      token = null;
      document.getElementById('app').classList.add('hidden');
      document.getElementById('auth-page').classList.remove('hidden');
    });

    let isSignUp = false;
    document.getElementById('auth-toggle').addEventListener('click', (e) => {
      e.preventDefault();
      isSignUp = !isSignUp;
      document.getElementById('auth-subtitle').textContent = isSignUp ? 'Create your account' : 'Sign in to your account';
      document.getElementById('auth-submit').textContent = isSignUp ? 'Sign Up' : 'Sign In';
      document.getElementById('auth-toggle-text').textContent = isSignUp ? 'Already have an account?' : "Don't have an account?";
      document.getElementById('auth-toggle').textContent = isSignUp ? 'Sign in' : 'Sign up';
      document.getElementById('name-group').classList.toggle('hidden', !isSignUp);
    });

    document.getElementById('auth-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const email = document.getElementById('email').value;
      const password = document.getElementById('password').value;
      const name = document.getElementById('name').value;
      const errorEl = document.getElementById('auth-error');

      try {
        if (isSignUp) {
          await api('/auth/signup', { method: 'POST', body: JSON.stringify({ email, password, name }) });
          alert('Account created! Please sign in.');
          document.getElementById('auth-toggle').click();
        } else {
          const result = await api('/auth/login', { method: 'POST', body: JSON.stringify({ email, password }) });
          token = result.token;
          localStorage.setItem('token', token);
          currentUser = result.user;
          document.getElementById('auth-page').classList.add('hidden');
          document.getElementById('app').classList.remove('hidden');
          showPage('overview');
        }
        errorEl.classList.remove('show');
      } catch (err) {
        errorEl.textContent = err.message;
        errorEl.classList.add('show');
      }
    });

    if (token) {
      api('/auth/me')
        .then(user => {
          currentUser = user;
          document.getElementById('auth-page').classList.add('hidden');
          document.getElementById('app').classList.remove('hidden');
          showPage('overview');
        })
        .catch(() => {
          localStorage.removeItem('token');
          token = null;
        });
    }
  </script>
</body>
</html>`;
}

export function createServer(config: Config) {
  const app = express();
  const router = new OperationRouter(config);
  const authEnabled = config.auth?.enabled ?? false;

  if (authEnabled && config.auth?.pocketbase) {
    initPocketBase({
      url: config.auth.pocketbase.url,
      adminEmail: config.auth.pocketbase.admin_email,
      adminPassword: config.auth.pocketbase.admin_password,
    });
  }

  app.use(cors());
  app.use(express.json({ limit: '50mb' }));

  app.get('/', (_req: Request, res: Response) => {
    res.type('html').send(getHomepageHtml());
  });

  app.get('/docs', (_req: Request, res: Response) => {
    res.type('html').send(getDocsHtml());
  });

  if (authEnabled) {
    app.get('/dashboard', (_req: Request, res: Response) => {
      res.type('html').send(getDashboardHtml());
    });

    app.use('/api/auth', createAuthRoutes());
    app.use('/api/keys', createApiKeysRoutes());
    app.use('/api/usage', createUsageRoutes());
  }

  app.get('/api/openapi.json', (_req: Request, res: Response) => {
    const openApiSpec = generateOpenAPISpec(config);
    res.json(openApiSpec);
  });

  app.get('/api/health', (_req: Request, res: Response) => {
    res.json({ status: 'ok', auth: authEnabled });
  });

  app.get('/api/operations', (_req: Request, res: Response) => {
    res.json({
      operations: router.getAvailableOperations().map(op => ({
        name: op,
        provider: router.getProviderForOperation(op),
      })),
    });
  });

  const operationHandler = async (req: Request, res: Response) => {
    const { operation } = req.params;
    const { image, options } = req.body;
    const startTime = Date.now();

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

      const latencyMs = Date.now() - startTime;
      const base64Result = result.image.toString('base64');

      if (authEnabled && req.auth) {
        logUsage({
          userId: req.auth.user.id,
          apiKeyId: req.auth.apiKey.id,
          operation,
          status: 'success',
          latencyMs,
          requestId: req.auth.requestId,
        }).catch(console.error);
      }

      res.json({
        image: `data:${result.mimeType};base64,${base64Result}`,
        metadata: result.metadata,
      });
    } catch (error) {
      const latencyMs = Date.now() - startTime;
      const message = error instanceof Error ? error.message : 'Unknown error';
      console.error(`Operation ${operation} failed:`, message);

      if (authEnabled && req.auth) {
        logUsage({
          userId: req.auth.user.id,
          apiKeyId: req.auth.apiKey.id,
          operation,
          status: 'error',
          latencyMs,
          requestId: req.auth.requestId,
          errorMessage: message,
        }).catch(console.error);
      }

      res.status(500).json({ error: message });
    }
  };

  if (authEnabled) {
    app.post('/api/v1/:operation', requireApiKey(), operationHandler);
  } else {
    app.post('/api/v1/:operation', operationHandler);
  }

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
