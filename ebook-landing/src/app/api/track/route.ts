import { NextResponse } from 'next/server';
import prisma from '@/lib/prisma';

export async function POST(req: Request) {
  try {
    const data = await req.json();
    const { sessionId, email, whatsapp, referrer } = data;

    if (!sessionId) {
      return NextResponse.json({ error: 'Session ID is required' }, { status: 400 });
    }

    const visitor = await prisma.visitor.upsert({
      where: { sessionId },
      update: {
        ...(email && { email }),
        ...(whatsapp && { whatsapp }),
        ...(referrer && { referrer }),
      },
      create: {
        sessionId,
        email,
        whatsapp,
        referrer,
      },
    });

    return NextResponse.json(visitor);
  } catch (error) {
    console.error('Tracking Error:', error);
    return NextResponse.json({ error: 'Internal Server Error' }, { status: 500 });
  }
}
