import { ImageResponse } from 'next/og';

export const runtime = 'edge';

export async function GET() {
  return new ImageResponse(
    (
      <div
        style={{
          width: '100%',
          height: '100%',
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          justifyContent: 'center',
          background: '#000',
          position: 'relative',
        }}
      >
        <div
          style={{
            position: 'absolute',
            top: '10%',
            left: '20%',
            width: 400,
            height: 400,
            background: 'radial-gradient(circle, rgba(0,112,243,0.3) 0%, transparent 70%)',
            borderRadius: '50%',
          }}
        />
        <div
          style={{
            position: 'absolute',
            bottom: '10%',
            right: '15%',
            width: 350,
            height: 350,
            background: 'radial-gradient(circle, rgba(121,40,202,0.25) 0%, transparent 70%)',
            borderRadius: '50%',
          }}
        />

        <div
          style={{
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
            justifyContent: 'center',
            zIndex: 1,
          }}
        >
          <div
            style={{
              display: 'flex',
              alignItems: 'center',
              gap: 16,
              marginBottom: 32,
            }}
          >
            <span style={{ fontSize: 64 }}>☀️</span>
            <span
              style={{
                fontSize: 48,
                fontWeight: 700,
                color: '#fff',
                letterSpacing: '-0.02em',
              }}
            >
              Brighten
            </span>
          </div>

          <div
            style={{
              display: 'flex',
              flexDirection: 'column',
              alignItems: 'center',
              gap: 8,
            }}
          >
            <span
              style={{
                fontSize: 56,
                fontWeight: 700,
                color: '#fff',
                letterSpacing: '-0.03em',
              }}
            >
              Powerful AI image editing APIs
            </span>
            <span
              style={{
                fontSize: 56,
                fontWeight: 700,
                background: 'linear-gradient(90deg, #0070f3 0%, #00d4ff 50%, #7928ca 100%)',
                backgroundClip: 'text',
                color: 'transparent',
                letterSpacing: '-0.03em',
              }}
            >
              built for developers
            </span>
          </div>

          <div
            style={{
              display: 'flex',
              gap: 12,
              marginTop: 48,
            }}
          >
            {['Background Removal', 'Upscale', 'Restore', 'Colorize'].map((op) => (
              <div
                key={op}
                style={{
                  padding: '10px 20px',
                  background: 'rgba(255,255,255,0.08)',
                  border: '1px solid rgba(255,255,255,0.15)',
                  borderRadius: 8,
                  fontSize: 20,
                  color: '#888',
                }}
              >
                {op}
              </div>
            ))}
          </div>
        </div>

        <div
          style={{
            position: 'absolute',
            bottom: 40,
            display: 'flex',
            alignItems: 'center',
            gap: 8,
            color: '#444',
            fontSize: 18,
          }}
        >
          <span>api.brighten.dev</span>
        </div>
      </div>
    ),
    {
      width: 1200,
      height: 630,
    }
  );
}
