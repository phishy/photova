import { NextRequest, NextResponse } from 'next/server';
import { users } from '@/lib/pocketbase';

export async function POST(request: NextRequest) {
  try {
    const { email, password } = await request.json();
    
    if (!email || !password) {
      return NextResponse.json({ error: 'Email and password required' }, { status: 400 });
    }
    
    const result = await users.authenticate(email, password);
    
    const response = NextResponse.json({
      token: result.token,
      user: {
        id: result.record.id,
        email: result.record.email,
        name: result.record.name,
      },
    });
    
    response.cookies.set('token', result.token, {
      httpOnly: true,
      secure: process.env.NODE_ENV === 'production',
      sameSite: 'lax',
      path: '/',
      maxAge: 60 * 60 * 24 * 7,
    });
    
    return response;
  } catch (error) {
    return NextResponse.json({ error: 'Invalid credentials' }, { status: 401 });
  }
}
