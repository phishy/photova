import { NextRequest, NextResponse } from 'next/server';
import { users } from '@/lib/pocketbase';

export async function POST(request: NextRequest) {
  try {
    const { email, password, name } = await request.json();
    
    if (!email || !password) {
      return NextResponse.json({ error: 'Email and password required' }, { status: 400 });
    }
    
    const user = await users.create({
      email,
      password,
      name: name || email.split('@')[0],
    });
    
    return NextResponse.json({ id: user.id, email: user.email, name: user.name });
  } catch (error) {
    const message = error instanceof Error ? error.message : 'Signup failed';
    return NextResponse.json({ error: message }, { status: 400 });
  }
}
