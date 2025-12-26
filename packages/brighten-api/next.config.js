const path = require('path')

/** @type {import('next').NextConfig} */
const nextConfig = {
  output: 'standalone',
  transpilePackages: ['brighten'],
  experimental: {
    outputFileTracingRoot: path.join(__dirname, '../../'),
    serverActions: {
      bodySizeLimit: '10mb',
    },
  },
}

module.exports = nextConfig
