const fs = require("fs");
const path = require("path");
const zlib = require("zlib");

const outputDir = path.resolve(__dirname, "../assets/images");

function clamp(value, min, max) {
    return Math.max(min, Math.min(max, value));
}

function hexToRgb(hex) {
    const normalized = hex.replace("#", "");

    return {
        r: parseInt(normalized.slice(0, 2), 16),
        g: parseInt(normalized.slice(2, 4), 16),
        b: parseInt(normalized.slice(4, 6), 16),
    };
}

function setPixel(buffer, size, x, y, color) {
    if (x < 0 || x >= size || y < 0 || y >= size) {
        return;
    }

    const index = (y * size + x) * 4;
    buffer[index] = color.r;
    buffer[index + 1] = color.g;
    buffer[index + 2] = color.b;
    buffer[index + 3] = color.a;
}

function fillRoundedRect(buffer, size, rect, color) {
    const xStart = Math.floor(rect.x);
    const yStart = Math.floor(rect.y);
    const xEnd = Math.ceil(rect.x + rect.width);
    const yEnd = Math.ceil(rect.y + rect.height);

    for (let y = yStart; y < yEnd; y += 1) {
        for (let x = xStart; x < xEnd; x += 1) {
            const dx = x + 0.5 < rect.x + rect.radius
                ? rect.x + rect.radius - (x + 0.5)
                : x + 0.5 > rect.x + rect.width - rect.radius
                    ? (x + 0.5) - (rect.x + rect.width - rect.radius)
                    : 0;
            const dy = y + 0.5 < rect.y + rect.radius
                ? rect.y + rect.radius - (y + 0.5)
                : y + 0.5 > rect.y + rect.height - rect.radius
                    ? (y + 0.5) - (rect.y + rect.height - rect.radius)
                    : 0;

            if (dx === 0 || dy === 0 || (dx * dx) + (dy * dy) <= rect.radius * rect.radius) {
                setPixel(buffer, size, x, y, color);
            }
        }
    }
}

function drawLine(buffer, size, start, end, thickness, color) {
    const minX = Math.floor(Math.min(start.x, end.x) - thickness - 1);
    const maxX = Math.ceil(Math.max(start.x, end.x) + thickness + 1);
    const minY = Math.floor(Math.min(start.y, end.y) - thickness - 1);
    const maxY = Math.ceil(Math.max(start.y, end.y) + thickness + 1);
    const dx = end.x - start.x;
    const dy = end.y - start.y;
    const lengthSquared = (dx * dx) + (dy * dy) || 1;

    for (let y = minY; y <= maxY; y += 1) {
        for (let x = minX; x <= maxX; x += 1) {
            const px = x + 0.5;
            const py = y + 0.5;
            const t = clamp((((px - start.x) * dx) + ((py - start.y) * dy)) / lengthSquared, 0, 1);
            const closestX = start.x + (dx * t);
            const closestY = start.y + (dy * t);
            const distance = Math.hypot(px - closestX, py - closestY);

            if (distance <= thickness / 2) {
                setPixel(buffer, size, x, y, color);
            }
        }
    }
}

function createFaviconPixels(size) {
    const pixels = Buffer.alloc(size * size * 4, 0);
    const navyTop = hexToRgb("#101827");
    const navyBottom = hexToRgb("#070B14");
    const gold = hexToRgb("#C6A46A");
    const goldLight = hexToRgb("#E8D6B4");

    for (let y = 0; y < size; y += 1) {
        const mix = y / Math.max(1, size - 1);
        const rowColor = {
            r: Math.round((navyTop.r * (1 - mix)) + (navyBottom.r * mix)),
            g: Math.round((navyTop.g * (1 - mix)) + (navyBottom.g * mix)),
            b: Math.round((navyTop.b * (1 - mix)) + (navyBottom.b * mix)),
            a: 255,
        };

        for (let x = 0; x < size; x += 1) {
            setPixel(pixels, size, x, y, rowColor);
        }
    }

    fillRoundedRect(
        pixels,
        size,
        {
            x: size * 0.06,
            y: size * 0.06,
            width: size * 0.88,
            height: size * 0.88,
            radius: size * 0.18,
        },
        { ...gold, a: 28 }
    );

    fillRoundedRect(
        pixels,
        size,
        {
            x: size * 0.09,
            y: size * 0.09,
            width: size * 0.82,
            height: size * 0.82,
            radius: size * 0.17,
        },
        { ...navyBottom, a: 255 }
    );

    drawLine(
        pixels,
        size,
        { x: size * 0.30, y: size * 0.23 },
        { x: size * 0.30, y: size * 0.77 },
        size * 0.12,
        { ...gold, a: 255 }
    );
    drawLine(
        pixels,
        size,
        { x: size * 0.35, y: size * 0.50 },
        { x: size * 0.68, y: size * 0.23 },
        size * 0.11,
        { ...gold, a: 255 }
    );
    drawLine(
        pixels,
        size,
        { x: size * 0.35, y: size * 0.50 },
        { x: size * 0.71, y: size * 0.78 },
        size * 0.11,
        { ...gold, a: 255 }
    );
    drawLine(
        pixels,
        size,
        { x: size * 0.65, y: size * 0.22 },
        { x: size * 0.80, y: size * 0.22 },
        size * 0.06,
        { ...goldLight, a: 255 }
    );
    drawLine(
        pixels,
        size,
        { x: size * 0.80, y: size * 0.22 },
        { x: size * 0.80, y: size * 0.37 },
        size * 0.06,
        { ...goldLight, a: 255 }
    );

    return pixels;
}

function createPng(size) {
    const pixels = createFaviconPixels(size);
    const stride = (size * 4) + 1;
    const raw = Buffer.alloc(stride * size);

    for (let y = 0; y < size; y += 1) {
        const rowOffset = y * stride;
        const srcOffset = y * size * 4;
        raw[rowOffset] = 0;
        pixels.copy(raw, rowOffset + 1, srcOffset, srcOffset + (size * 4));
    }

    const compressed = zlib.deflateSync(raw);

    return Buffer.concat([
        Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]),
        pngChunk("IHDR", Buffer.from([
            0, 0, 0, size,
            0, 0, 0, size,
            8,
            6,
            0,
            0,
            0,
        ])),
        pngChunk("IDAT", compressed),
        pngChunk("IEND", Buffer.alloc(0)),
    ]);
}

function pngChunk(type, data) {
    const typeBuffer = Buffer.from(type);
    const length = Buffer.alloc(4);
    length.writeUInt32BE(data.length, 0);
    const crc = Buffer.alloc(4);
    crc.writeUInt32BE(crc32(Buffer.concat([typeBuffer, data])), 0);

    return Buffer.concat([length, typeBuffer, data, crc]);
}

function crc32(buffer) {
    let crc = 0xffffffff;

    for (const byte of buffer) {
        crc ^= byte;

        for (let bit = 0; bit < 8; bit += 1) {
            const mask = -(crc & 1);
            crc = (crc >>> 1) ^ (0xedb88320 & mask);
        }
    }

    return (crc ^ 0xffffffff) >>> 0;
}

function createIco(pngBuffer) {
    const header = Buffer.alloc(6);
    header.writeUInt16LE(0, 0);
    header.writeUInt16LE(1, 2);
    header.writeUInt16LE(1, 4);

    const directory = Buffer.alloc(16);
    directory.writeUInt8(32, 0);
    directory.writeUInt8(32, 1);
    directory.writeUInt8(0, 2);
    directory.writeUInt8(0, 3);
    directory.writeUInt16LE(1, 4);
    directory.writeUInt16LE(32, 6);
    directory.writeUInt32LE(pngBuffer.length, 8);
    directory.writeUInt32LE(header.length + directory.length, 12);

    return Buffer.concat([header, directory, pngBuffer]);
}

fs.mkdirSync(outputDir, { recursive: true });

const png32 = createPng(32);
const png180 = createPng(180);
const ico = createIco(png32);

fs.writeFileSync(path.join(outputDir, "favicon-32x32.png"), png32);
fs.writeFileSync(path.join(outputDir, "apple-touch-icon.png"), png180);
fs.writeFileSync(path.join(outputDir, "favicon.ico"), ico);

console.log("Generated favicon assets in", outputDir);
