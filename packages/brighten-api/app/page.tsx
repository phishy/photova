'use client';

import { useState, useEffect, useRef } from 'react';

const operations = [
  { id: 'background-remove', name: 'Background Removal', desc: 'Remove backgrounds instantly' },
  { id: 'upscale', name: 'Upscale', desc: 'Enhance resolution up to 4x' },
  { id: 'restore', name: 'Restore', desc: 'Fix old or damaged photos' },
  { id: 'colorize', name: 'Colorize', desc: 'Add color to B&W images' },
  { id: 'unblur', name: 'Deblur', desc: 'Sharpen blurry images' },
  { id: 'inpaint', name: 'Object Removal', desc: 'Remove unwanted objects' },
];

const codeExample = `const response = await fetch('https://api.brighten.dev/v1/background-remove', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer br_live_xxxxx',
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({ image: 'data:image/png;base64,...' }),
});

const { image } = await response.json();`;

const curlExample = `curl -X POST https://api.brighten.dev/v1/background-remove \\
  -H "Authorization: Bearer br_live_xxxxx" \\
  -H "Content-Type: application/json" \\
  -d '{"image": "data:image/png;base64,..."}'`;

function CodeBlock({ code, language }: { code: string; language: 'js' | 'curl' }) {
  const [copied, setCopied] = useState(false);

  const copyToClipboard = () => {
    navigator.clipboard.writeText(code);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  const highlightJS = (text: string) => {
    const lines = text.split('\n');
    return lines.map((line, i) => {
      let html = line
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/(const|await|let|var)(\s)/g, '<span style="color:#c678dd">$1</span>$2')
        .replace(/(fetch|JSON\.stringify)/g, '<span style="color:#61afef">$1</span>')
        .replace(/\.json\(\)/g, '.<span style="color:#61afef">json</span>()')
        .replace(/('https:\/\/[^']*')/g, '<span style="color:#98c379">$1</span>')
        .replace(/('Authorization'|'Content-Type'|'application\/json')/g, '<span style="color:#98c379">$1</span>')
        .replace(/('Bearer br_live_xxxxx'|'data:image\/png;base64,\.\.\.')/g, '<span style="color:#d19a66">$1</span>')
        .replace(/('POST')/g, '<span style="color:#98c379">$1</span>')
        .replace(/(method|headers|body|image):/g, '<span style="color:#e06c75">$1</span>:')
        .replace(/\b(response)\b/g, '<span style="color:#e5c07b">$1</span>');
      return html;
    }).join('\n');
  };

  const highlightCurl = (text: string) => {
    let html = text
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/^(curl)/gm, '<span style="color:#c678dd">$1</span>')
      .replace(/(-X|-H|-d)/g, '<span style="color:#61afef">$1</span>')
      .replace(/(POST)/g, '<span style="color:#e06c75">$1</span>')
      .replace(/(https:\/\/[^\s\\]+)/g, '<span style="color:#98c379">$1</span>')
      .replace(/("Authorization: Bearer br_live_xxxxx")/g, '<span style="color:#d19a66">$1</span>')
      .replace(/("Content-Type: application\/json")/g, '<span style="color:#98c379">$1</span>')
      .replace(/'(\{"image":[^}]+\})'/g, '\'<span style="color:#98c379">$1</span>\'');
    return html;
  };

  const highlighted = language === 'js' ? highlightJS(code) : highlightCurl(code);

  return (
    <div className="code-block" style={{ position: 'relative' }}>
      <button
        onClick={copyToClipboard}
        style={{
          position: 'absolute',
          top: 12,
          right: 12,
          padding: '6px 12px',
          background: copied ? 'rgba(34,197,94,0.15)' : 'rgba(255,255,255,0.06)',
          border: '1px solid',
          borderColor: copied ? 'rgba(34,197,94,0.3)' : 'rgba(255,255,255,0.1)',
          borderRadius: 6,
          color: copied ? '#22c55e' : '#666',
          fontSize: 12,
          fontWeight: 500,
          cursor: 'pointer',
          transition: 'all 0.15s',
          display: 'flex',
          alignItems: 'center',
          gap: 6,
        }}
        onMouseEnter={e => { if (!copied) { e.currentTarget.style.background = 'rgba(255,255,255,0.1)'; e.currentTarget.style.color = '#aaa'; }}}
        onMouseLeave={e => { if (!copied) { e.currentTarget.style.background = 'rgba(255,255,255,0.06)'; e.currentTarget.style.color = '#666'; }}}
      >
        {copied ? (
          <>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
              <polyline points="20 6 9 17 4 12" />
            </svg>
            Copied!
          </>
        ) : (
          <>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
              <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
            </svg>
            Copy
          </>
        )}
      </button>
      <pre style={{
        padding: 24,
        margin: 0,
        fontSize: 13,
        lineHeight: 1.8,
        overflow: 'auto',
        fontFamily: "'SF Mono', 'Fira Code', Monaco, Consolas, monospace",
      }}>
        <code dangerouslySetInnerHTML={{ __html: highlighted }} style={{ color: '#abb2bf' }} />
      </pre>
    </div>
  );
}

export default function HomePage() {
  const [isLogin, setIsLogin] = useState(false);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [name, setName] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [codeTab, setCodeTab] = useState<'js' | 'curl'>('js');
  const authRef = useRef<HTMLDivElement>(null);

  const [isLoggedIn, setIsLoggedIn] = useState(false);

  useEffect(() => {
    fetch('/api/auth/me', { credentials: 'include' })
      .then(r => r.ok ? r.json() : Promise.reject())
      .then(() => setIsLoggedIn(true))
      .catch(() => {});
  }, []);

  const scrollToAuth = () => authRef.current?.scrollIntoView({ behavior: 'smooth' });

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      if (isLogin) {
        const res = await fetch('/api/auth/login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email, password }),
          credentials: 'include',
        });
        if (!res.ok) throw new Error((await res.json()).error || 'Login failed');
        window.location.href = '/dashboard';
      } else {
        const signupRes = await fetch('/api/auth/signup', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email, password, name: name || email.split('@')[0] }),
          credentials: 'include',
        });
        if (!signupRes.ok) throw new Error((await signupRes.json()).error || 'Signup failed');
        const loginRes = await fetch('/api/auth/login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email, password }),
          credentials: 'include',
        });
        if (!loginRes.ok) throw new Error('Account created! Please sign in.');
        window.location.href = '/dashboard';
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'An error occurred');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={{ minHeight: '100vh', background: '#000' }}>
      <style dangerouslySetInnerHTML={{ __html: `
        @media (max-width: 640px) {
          .nav-links { display: none !important; }
          .code-block pre { font-size: 11px !important; padding: 16px !important; }
          .hero-title { font-size: 32px !important; }
          .hero-subtitle { font-size: 28px !important; }
          .section-title { font-size: 28px !important; }
          .popular-badge { top: -10px !important; font-size: 10px !important; padding: 3px 10px !important; }
        }
      `}} />
      <header style={{
        position: 'fixed',
        top: 0,
        left: 0,
        right: 0,
        zIndex: 100,
        padding: '0 24px',
        height: 64,
        display: 'flex',
        alignItems: 'center',
        background: 'rgba(0,0,0,0.5)',
        backdropFilter: 'blur(12px)',
        borderBottom: '1px solid rgba(255,255,255,0.05)',
      }}>
        <div style={{ maxWidth: 1200, margin: '0 auto', width: '100%', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <a href="/" style={{ display: 'flex', alignItems: 'center', gap: 8, fontWeight: 600, fontSize: 15 }}>
            <span style={{ fontSize: 20 }}>☀️</span>
            <span>Brighten</span>
          </a>
          <nav style={{ display: 'flex', alignItems: 'center', gap: 32 }}>
            <a href="#features" className="nav-links" style={{ fontSize: 14, color: '#888', transition: 'color 0.15s' }} onMouseEnter={e => e.currentTarget.style.color = '#fff'} onMouseLeave={e => e.currentTarget.style.color = '#888'}>Features</a>
            <a href="#pricing" className="nav-links" style={{ fontSize: 14, color: '#888', transition: 'color 0.15s' }} onMouseEnter={e => e.currentTarget.style.color = '#fff'} onMouseLeave={e => e.currentTarget.style.color = '#888'}>Pricing</a>
            <a href="/docs" className="nav-links" style={{ fontSize: 14, color: '#888', transition: 'color 0.15s' }} onMouseEnter={e => e.currentTarget.style.color = '#fff'} onMouseLeave={e => e.currentTarget.style.color = '#888'}>Docs</a>
{isLoggedIn ? (
              <a
                href="/dashboard"
                style={{
                  padding: '8px 16px',
                  background: '#fff',
                  color: '#000',
                  borderRadius: 6,
                  fontSize: 14,
                  fontWeight: 500,
                  transition: 'all 0.15s',
                }}
                onMouseEnter={e => { e.currentTarget.style.background = '#eee'; }}
                onMouseLeave={e => { e.currentTarget.style.background = '#fff'; }}
              >
                Dashboard
              </a>
            ) : (
              <button
                onClick={scrollToAuth}
                style={{
                  padding: '8px 16px',
                  background: '#fff',
                  color: '#000',
                  borderRadius: 6,
                  fontSize: 14,
                  fontWeight: 500,
                  transition: 'all 0.15s',
                }}
                onMouseEnter={e => { e.currentTarget.style.background = '#eee'; }}
                onMouseLeave={e => { e.currentTarget.style.background = '#fff'; }}
              >
                Get Started
              </button>
            )}
          </nav>
        </div>
      </header>

      <section style={{
        minHeight: '100vh',
        display: 'flex',
        flexDirection: 'column',
        justifyContent: 'center',
        alignItems: 'center',
        padding: '120px 24px 80px',
        position: 'relative',
        overflow: 'hidden',
      }}>
        <svg
          style={{
            position: 'absolute',
            bottom: 0,
            left: 0,
            width: '200%',
            height: '60%',
            opacity: 0.4,
            pointerEvents: 'none',
            animation: 'wave-drift 20s ease-in-out infinite',
          }}
          viewBox="0 0 1440 320"
          preserveAspectRatio="none"
        >
          <defs>
            <linearGradient id="wave-gradient-1" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" stopColor="#0070f3" stopOpacity="0.3" />
              <stop offset="50%" stopColor="#7928ca" stopOpacity="0.2" />
              <stop offset="100%" stopColor="#00d4ff" stopOpacity="0.3" />
            </linearGradient>
          </defs>
          <path
            fill="url(#wave-gradient-1)"
            d="M0,160L48,176C96,192,192,224,288,213.3C384,203,480,149,576,138.7C672,128,768,160,864,181.3C960,203,1056,213,1152,197.3C1248,181,1344,139,1392,117.3L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"
          />
        </svg>
        <svg
          style={{
            position: 'absolute',
            bottom: 0,
            left: '-50%',
            width: '200%',
            height: '50%',
            opacity: 0.3,
            pointerEvents: 'none',
            animation: 'wave-drift-reverse 25s ease-in-out infinite',
          }}
          viewBox="0 0 1440 320"
          preserveAspectRatio="none"
        >
          <defs>
            <linearGradient id="wave-gradient-2" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" stopColor="#7928ca" stopOpacity="0.3" />
              <stop offset="50%" stopColor="#0070f3" stopOpacity="0.2" />
              <stop offset="100%" stopColor="#7928ca" stopOpacity="0.3" />
            </linearGradient>
          </defs>
          <path
            fill="url(#wave-gradient-2)"
            d="M0,64L48,80C96,96,192,128,288,128C384,128,480,96,576,106.7C672,117,768,171,864,181.3C960,192,1056,160,1152,133.3C1248,107,1344,85,1392,74.7L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"
          />
        </svg>
        <svg
          style={{
            position: 'absolute',
            bottom: 0,
            left: '-25%',
            width: '200%',
            height: '40%',
            opacity: 0.25,
            pointerEvents: 'none',
            animation: 'wave-drift 30s ease-in-out infinite',
          }}
          viewBox="0 0 1440 320"
          preserveAspectRatio="none"
        >
          <defs>
            <linearGradient id="wave-gradient-3" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" stopColor="#00d4ff" stopOpacity="0.4" />
              <stop offset="100%" stopColor="#0070f3" stopOpacity="0.2" />
            </linearGradient>
          </defs>
          <path
            fill="url(#wave-gradient-3)"
            d="M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,218.7C672,235,768,245,864,234.7C960,224,1056,192,1152,176C1248,160,1344,160,1392,160L1440,149L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"
          />
        </svg>

        <div style={{
          position: 'absolute',
          top: '20%',
          left: '50%',
          transform: 'translateX(-50%)',
          width: 800,
          height: 800,
          background: 'radial-gradient(circle, rgba(0,112,243,0.15) 0%, transparent 60%)',
          pointerEvents: 'none',
        }} />
        <div style={{
          position: 'absolute',
          top: '30%',
          left: '30%',
          width: 600,
          height: 600,
          background: 'radial-gradient(circle, rgba(121,40,202,0.1) 0%, transparent 60%)',
          pointerEvents: 'none',
        }} />

        <div style={{ position: 'relative', zIndex: 1, textAlign: 'center', maxWidth: 900 }}>
          <div style={{
            display: 'inline-flex',
            alignItems: 'center',
            gap: 8,
            padding: '6px 14px',
            background: 'rgba(255,255,255,0.05)',
            border: '1px solid rgba(255,255,255,0.1)',
            borderRadius: 100,
            fontSize: 13,
            color: '#888',
            marginBottom: 32,
          }}>
            <span style={{ width: 6, height: 6, borderRadius: '50%', background: '#0070f3', animation: 'pulse 2s infinite' }} />
            Now in public beta
          </div>

          <h1 style={{
            fontSize: 'clamp(40px, 8vw, 72px)',
            fontWeight: 700,
            letterSpacing: '-0.04em',
            lineHeight: 1.1,
            marginBottom: 24,
          }}>
            AI image processing
            <br />
            <span style={{
              background: 'linear-gradient(135deg, #0070f3 0%, #00d4ff 50%, #7928ca 100%)',
              backgroundSize: '200% auto',
              WebkitBackgroundClip: 'text',
              WebkitTextFillColor: 'transparent',
              animation: 'gradient-x 8s linear infinite',
            }}>
              in one API call
            </span>
          </h1>

          <p style={{
            fontSize: 'clamp(16px, 2vw, 20px)',
            color: '#888',
            maxWidth: 600,
            margin: '0 auto 40px',
            lineHeight: 1.6,
          }}>
            Background removal, upscaling, restoration, and more. Built for developers who ship fast.
          </p>

          <div style={{ display: 'flex', gap: 16, justifyContent: 'center', flexWrap: 'wrap' }}>
            {isLoggedIn ? (
              <a
                href="/dashboard"
                style={{
                  padding: '12px 24px',
                  background: '#fff',
                  color: '#000',
                  borderRadius: 8,
                  fontSize: 15,
                  fontWeight: 500,
                  transition: 'all 0.15s',
                }}
                onMouseEnter={e => { e.currentTarget.style.transform = 'translateY(-2px)'; e.currentTarget.style.boxShadow = '0 8px 30px rgba(255,255,255,0.15)'; }}
                onMouseLeave={e => { e.currentTarget.style.transform = 'translateY(0)'; e.currentTarget.style.boxShadow = 'none'; }}
              >
                Go to Dashboard →
              </a>
            ) : (
              <button
                onClick={scrollToAuth}
                style={{
                  padding: '12px 24px',
                  background: '#fff',
                  color: '#000',
                  borderRadius: 8,
                  fontSize: 15,
                  fontWeight: 500,
                  transition: 'all 0.15s',
                }}
                onMouseEnter={e => { e.currentTarget.style.transform = 'translateY(-2px)'; e.currentTarget.style.boxShadow = '0 8px 30px rgba(255,255,255,0.15)'; }}
                onMouseLeave={e => { e.currentTarget.style.transform = 'translateY(0)'; e.currentTarget.style.boxShadow = 'none'; }}
              >
                Start for free →
              </button>
            )}
            <a
              href="/docs"
              style={{
                padding: '12px 24px',
                background: 'transparent',
                color: '#fff',
                borderRadius: 8,
                fontSize: 15,
                fontWeight: 500,
                border: '1px solid rgba(255,255,255,0.2)',
                transition: 'all 0.15s',
              }}
              onMouseEnter={e => { e.currentTarget.style.borderColor = 'rgba(255,255,255,0.4)'; }}
              onMouseLeave={e => { e.currentTarget.style.borderColor = 'rgba(255,255,255,0.2)'; }}
            >
              Read the docs
            </a>
          </div>
        </div>

        <div style={{
          marginTop: 80,
          display: 'flex',
          gap: 12,
          flexWrap: 'wrap',
          justifyContent: 'center',
          maxWidth: 800,
        }}>
          {operations.map(op => (
            <div
              key={op.id}
              style={{
                padding: '10px 16px',
                background: 'rgba(255,255,255,0.03)',
                border: '1px solid rgba(255,255,255,0.08)',
                borderRadius: 8,
                fontSize: 13,
                color: '#888',
                transition: 'all 0.2s',
              }}
              onMouseEnter={e => { e.currentTarget.style.borderColor = 'rgba(0,112,243,0.5)'; e.currentTarget.style.color = '#fff'; }}
              onMouseLeave={e => { e.currentTarget.style.borderColor = 'rgba(255,255,255,0.08)'; e.currentTarget.style.color = '#888'; }}
            >
              {op.name}
            </div>
          ))}
        </div>
      </section>

      <section id="features" style={{ padding: '120px 24px', background: '#000', borderTop: '1px solid rgba(255,255,255,0.05)' }}>
        <div style={{ maxWidth: 1200, margin: '0 auto' }}>
          <div style={{ textAlign: 'center', marginBottom: 64 }}>
            <p style={{ fontSize: 13, color: '#0070f3', fontWeight: 500, marginBottom: 12, textTransform: 'uppercase', letterSpacing: '0.1em' }}>Developer Experience</p>
            <h2 style={{ fontSize: 'clamp(32px, 5vw, 48px)', fontWeight: 700, letterSpacing: '-0.03em', marginBottom: 16 }}>
              Simple API, powerful results
            </h2>
            <p style={{ fontSize: 18, color: '#666', maxWidth: 500, margin: '0 auto' }}>
              One endpoint per operation. JSON in, JSON out. Ship in minutes.
            </p>
          </div>

          <div style={{
            background: '#0a0a0a',
            borderRadius: 16,
            border: '1px solid rgba(255,255,255,0.08)',
            overflow: 'hidden',
          }}>
            <div style={{
              display: 'flex',
              borderBottom: '1px solid rgba(255,255,255,0.08)',
              padding: '0 20px',
            }}>
              <button
                onClick={() => setCodeTab('js')}
                style={{
                  padding: '16px 20px',
                  fontSize: 13,
                  fontWeight: 500,
                  color: codeTab === 'js' ? '#fff' : '#666',
                  borderBottom: codeTab === 'js' ? '2px solid #0070f3' : '2px solid transparent',
                  marginBottom: -1,
                  transition: 'all 0.15s',
                }}
              >
                JavaScript
              </button>
              <button
                onClick={() => setCodeTab('curl')}
                style={{
                  padding: '16px 20px',
                  fontSize: 13,
                  fontWeight: 500,
                  color: codeTab === 'curl' ? '#fff' : '#666',
                  borderBottom: codeTab === 'curl' ? '2px solid #0070f3' : '2px solid transparent',
                  marginBottom: -1,
                  transition: 'all 0.15s',
                }}
              >
                cURL
              </button>
            </div>
            <CodeBlock code={codeTab === 'js' ? codeExample : curlExample} language={codeTab} />
          </div>

          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))', gap: 16, marginTop: 48 }}>
            {[
              { title: '< 500ms', desc: 'Average response time', icon: '⚡' },
              { title: '99.9%', desc: 'Uptime SLA', icon: '🛡️' },
              { title: '10M+', desc: 'Images processed', icon: '📈' },
            ].map(stat => (
              <div
                key={stat.title}
                style={{
                  padding: 24,
                  background: 'rgba(255,255,255,0.02)',
                  border: '1px solid rgba(255,255,255,0.05)',
                  borderRadius: 12,
                  textAlign: 'center',
                }}
              >
                <div style={{ fontSize: 24, marginBottom: 8 }}>{stat.icon}</div>
                <div style={{ fontSize: 32, fontWeight: 700, marginBottom: 4 }}>{stat.title}</div>
                <div style={{ fontSize: 14, color: '#666' }}>{stat.desc}</div>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section style={{ padding: '120px 24px', background: '#000', borderTop: '1px solid rgba(255,255,255,0.05)' }}>
        <div style={{ maxWidth: 1200, margin: '0 auto' }}>
          <div style={{ textAlign: 'center', marginBottom: 64 }}>
            <p style={{ fontSize: 13, color: '#7928ca', fontWeight: 500, marginBottom: 12, textTransform: 'uppercase', letterSpacing: '0.1em' }}>Operations</p>
            <h2 style={{ fontSize: 'clamp(32px, 5vw, 48px)', fontWeight: 700, letterSpacing: '-0.03em' }}>
              Everything you need
            </h2>
          </div>

          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: 16 }}>
            {operations.map((op, i) => (
              <div
                key={op.id}
                style={{
                  padding: 32,
                  background: '#0a0a0a',
                  border: '1px solid rgba(255,255,255,0.08)',
                  borderRadius: 16,
                  transition: 'all 0.2s',
                  cursor: 'default',
                }}
                onMouseEnter={e => {
                  e.currentTarget.style.borderColor = 'rgba(0,112,243,0.3)';
                  e.currentTarget.style.transform = 'translateY(-4px)';
                }}
                onMouseLeave={e => {
                  e.currentTarget.style.borderColor = 'rgba(255,255,255,0.08)';
                  e.currentTarget.style.transform = 'translateY(0)';
                }}
              >
                <div style={{
                  width: 48,
                  height: 48,
                  borderRadius: 12,
                  background: `linear-gradient(135deg, ${['#0070f3', '#7928ca', '#ff0080', '#00d4ff', '#f97316', '#10b981'][i]} 0%, ${['#00d4ff', '#ff0080', '#f97316', '#7928ca', '#ff0080', '#0070f3'][i]} 100%)`,
                  marginBottom: 20,
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  fontSize: 20,
                }}>
                  {['✂️', '🔍', '🖼️', '🎨', '✨', '🧹'][i]}
                </div>
                <h3 style={{ fontSize: 18, fontWeight: 600, marginBottom: 8 }}>{op.name}</h3>
                <p style={{ fontSize: 14, color: '#666', lineHeight: 1.5 }}>{op.desc}</p>
                <code style={{
                  display: 'inline-block',
                  marginTop: 16,
                  padding: '6px 10px',
                  background: 'rgba(255,255,255,0.05)',
                  borderRadius: 6,
                  fontSize: 12,
                  color: '#888',
                  fontFamily: 'SF Mono, Monaco, monospace',
                }}>
                  /v1/{op.id}
                </code>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section id="pricing" style={{ padding: '120px 24px', background: '#000', borderTop: '1px solid rgba(255,255,255,0.05)' }}>
        <div style={{ maxWidth: 1000, margin: '0 auto' }}>
          <div style={{ textAlign: 'center', marginBottom: 64 }}>
            <p style={{ fontSize: 13, color: '#00d4ff', fontWeight: 500, marginBottom: 12, textTransform: 'uppercase', letterSpacing: '0.1em' }}>Pricing</p>
            <h2 style={{ fontSize: 'clamp(32px, 5vw, 48px)', fontWeight: 700, letterSpacing: '-0.03em', marginBottom: 16 }}>
              Start free, scale as you grow
            </h2>
          </div>

          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: 16 }}>
            {[
              { name: 'Free', price: '$0', period: '/month', requests: '100 requests/mo', features: ['All operations', 'API access', 'Community support'], cta: 'Get started' },
              { name: 'Pro', price: '$29', period: '/month', requests: '10,000 requests/mo', features: ['All operations', 'Priority processing', 'Email support', 'Webhooks'], popular: true, cta: 'Get started' },
              { name: 'Enterprise', price: 'Custom', period: '', requests: 'Unlimited', features: ['All operations', 'Dedicated support', 'SLA guarantee', 'Custom models'], cta: 'Contact us' },
            ].map(plan => (
              <div
                key={plan.name}
                style={{
                  padding: 32,
                  background: plan.popular ? 'linear-gradient(180deg, rgba(0,112,243,0.1) 0%, transparent 100%)' : '#0a0a0a',
                  border: plan.popular ? '1px solid rgba(0,112,243,0.3)' : '1px solid rgba(255,255,255,0.08)',
                  borderRadius: 16,
                  position: 'relative',
                }}
              >
                {plan.popular && (
                  <div className="popular-badge" style={{
                    position: 'absolute',
                    top: -12,
                    left: '50%',
                    transform: 'translateX(-50%)',
                    padding: '4px 12px',
                    background: '#0070f3',
                    borderRadius: 100,
                    fontSize: 11,
                    fontWeight: 600,
                    textTransform: 'uppercase',
                    letterSpacing: '0.05em',
                  }}>
                    Popular
                  </div>
                )}
                <div style={{ fontSize: 16, fontWeight: 600, marginBottom: 8 }}>{plan.name}</div>
                <div style={{ marginBottom: 8 }}>
                  <span style={{ fontSize: 48, fontWeight: 700 }}>{plan.price}</span>
                  <span style={{ color: '#666', fontSize: 14 }}>{plan.period}</span>
                </div>
                <div style={{ fontSize: 14, color: '#666', marginBottom: 24, paddingBottom: 24, borderBottom: '1px solid rgba(255,255,255,0.08)' }}>{plan.requests}</div>
                <ul style={{ listStyle: 'none', marginBottom: 32 }}>
                  {plan.features.map(f => (
                    <li key={f} style={{ fontSize: 14, color: '#888', marginBottom: 12, display: 'flex', alignItems: 'center', gap: 8 }}>
                      <span style={{ color: '#0070f3' }}>✓</span> {f}
                    </li>
                  ))}
                </ul>
                <button
                  onClick={scrollToAuth}
                  style={{
                    width: '100%',
                    padding: '12px 24px',
                    background: plan.popular ? '#fff' : 'transparent',
                    color: plan.popular ? '#000' : '#fff',
                    border: plan.popular ? 'none' : '1px solid rgba(255,255,255,0.2)',
                    borderRadius: 8,
                    fontSize: 14,
                    fontWeight: 500,
                    transition: 'all 0.15s',
                  }}
                  onMouseEnter={e => { e.currentTarget.style.opacity = '0.9'; }}
                  onMouseLeave={e => { e.currentTarget.style.opacity = '1'; }}
                >
                  {plan.cta}
                </button>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section ref={authRef} style={{ padding: '120px 24px', background: '#000', borderTop: '1px solid rgba(255,255,255,0.05)' }}>
        <div style={{ maxWidth: 400, margin: '0 auto' }}>
          <div style={{ textAlign: 'center', marginBottom: 32 }}>
            <h2 style={{ fontSize: 32, fontWeight: 700, letterSpacing: '-0.03em', marginBottom: 8 }}>
              {isLogin ? 'Welcome back' : 'Get started'}
            </h2>
            <p style={{ fontSize: 15, color: '#666' }}>
              {isLogin ? 'Sign in to your account' : 'Create your account in seconds'}
            </p>
          </div>

          <div style={{
            padding: 32,
            background: '#0a0a0a',
            border: '1px solid rgba(255,255,255,0.08)',
            borderRadius: 16,
          }}>
            {error && (
              <div style={{
                padding: '12px 16px',
                background: 'rgba(224,0,0,0.1)',
                border: '1px solid rgba(224,0,0,0.2)',
                borderRadius: 8,
                color: '#e00',
                fontSize: 14,
                marginBottom: 20,
              }}>
                {error}
              </div>
            )}

            <form onSubmit={handleSubmit}>
              {!isLogin && (
                <div style={{ marginBottom: 16 }}>
                  <label style={{ display: 'block', fontSize: 13, color: '#888', marginBottom: 8 }}>Name</label>
                  <input
                    type="text"
                    value={name}
                    onChange={e => setName(e.target.value)}
                    placeholder="Your name"
                    style={{
                      width: '100%',
                      padding: '12px 16px',
                      background: '#111',
                      border: '1px solid rgba(255,255,255,0.1)',
                      borderRadius: 8,
                      color: '#fff',
                      fontSize: 15,
                      outline: 'none',
                      transition: 'border-color 0.15s',
                    }}
                    onFocus={e => e.currentTarget.style.borderColor = 'rgba(0,112,243,0.5)'}
                    onBlur={e => e.currentTarget.style.borderColor = 'rgba(255,255,255,0.1)'}
                  />
                </div>
              )}

              <div style={{ marginBottom: 16 }}>
                <label style={{ display: 'block', fontSize: 13, color: '#888', marginBottom: 8 }}>Email</label>
                <input
                  type="email"
                  value={email}
                  onChange={e => setEmail(e.target.value)}
                  placeholder="you@example.com"
                  required
                  style={{
                    width: '100%',
                    padding: '12px 16px',
                    background: '#111',
                    border: '1px solid rgba(255,255,255,0.1)',
                    borderRadius: 8,
                    color: '#fff',
                    fontSize: 15,
                    outline: 'none',
                    transition: 'border-color 0.15s',
                  }}
                  onFocus={e => e.currentTarget.style.borderColor = 'rgba(0,112,243,0.5)'}
                  onBlur={e => e.currentTarget.style.borderColor = 'rgba(255,255,255,0.1)'}
                />
              </div>

              <div style={{ marginBottom: 24 }}>
                <label style={{ display: 'block', fontSize: 13, color: '#888', marginBottom: 8 }}>Password</label>
                <input
                  type="password"
                  value={password}
                  onChange={e => setPassword(e.target.value)}
                  placeholder={isLogin ? 'Enter password' : 'Create password'}
                  required
                  style={{
                    width: '100%',
                    padding: '12px 16px',
                    background: '#111',
                    border: '1px solid rgba(255,255,255,0.1)',
                    borderRadius: 8,
                    color: '#fff',
                    fontSize: 15,
                    outline: 'none',
                    transition: 'border-color 0.15s',
                  }}
                  onFocus={e => e.currentTarget.style.borderColor = 'rgba(0,112,243,0.5)'}
                  onBlur={e => e.currentTarget.style.borderColor = 'rgba(255,255,255,0.1)'}
                />
              </div>

              <button
                type="submit"
                disabled={loading}
                style={{
                  width: '100%',
                  padding: '12px 24px',
                  background: '#fff',
                  color: '#000',
                  border: 'none',
                  borderRadius: 8,
                  fontSize: 15,
                  fontWeight: 500,
                  opacity: loading ? 0.7 : 1,
                  transition: 'opacity 0.15s',
                }}
              >
                {loading ? 'Loading...' : isLogin ? 'Sign in' : 'Create account'}
              </button>
            </form>

            <div style={{ textAlign: 'center', marginTop: 20, fontSize: 14, color: '#666' }}>
              {isLogin ? "Don't have an account? " : 'Already have an account? '}
              <button
                onClick={() => setIsLogin(!isLogin)}
                style={{ color: '#0070f3', fontWeight: 500 }}
              >
                {isLogin ? 'Sign up' : 'Sign in'}
              </button>
            </div>
          </div>
        </div>
      </section>

      <footer style={{
        padding: '48px 24px',
        borderTop: '1px solid rgba(255,255,255,0.05)',
        background: '#000',
      }}>
        <div style={{ maxWidth: 1200, margin: '0 auto' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 24 }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 14, color: '#666' }}>
              <span style={{ fontSize: 16 }}>☀️</span>
              <span>Brighten</span>
              <span style={{ margin: '0 8px' }}>·</span>
              <span>© 2025</span>
            </div>
            <div style={{ display: 'flex', gap: 24 }}>
              <a href="/docs" style={{ fontSize: 14, color: '#666', transition: 'color 0.15s' }} onMouseEnter={e => e.currentTarget.style.color = '#fff'} onMouseLeave={e => e.currentTarget.style.color = '#666'}>Docs</a>
              <a href="https://github.com/phishy/brighten" style={{ fontSize: 14, color: '#666', transition: 'color 0.15s' }} onMouseEnter={e => e.currentTarget.style.color = '#fff'} onMouseLeave={e => e.currentTarget.style.color = '#666'}>GitHub</a>
            </div>
          </div>
        </div>
      </footer>
    </div>
  );
}
