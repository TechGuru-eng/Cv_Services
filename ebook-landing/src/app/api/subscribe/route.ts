import { NextResponse } from 'next/server';
import prisma from '@/lib/prisma';

export async function POST(req: Request) {
  try {
    const data = await req.json();
    const { sessionId, fullName, email, whatsapp, city, profession } = data;

    if (!fullName || !email || !whatsapp) {
      return NextResponse.json({ error: 'Missing required fields' }, { status: 400 });
    }

    const subscriber = await prisma.subscriber.create({
      data: {
        fullName,
        email,
        whatsapp,
        city,
        profession,
      },
    });

    if (sessionId) {
      await prisma.visitor.update({
        where: { sessionId },
        data: { converted: true, email, whatsapp },
      }).catch(() => null); // Ignore error if visitor session doesn't exist
    }

    return NextResponse.json(subscriber);
  } catch (error) {
    console.error('Subscribe Error:', error);
    return NextResponse.json({ error: 'Internal Server Error' }, { status: 500 });
  }
}
