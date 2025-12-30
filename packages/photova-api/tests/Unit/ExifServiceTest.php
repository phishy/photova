<?php

use App\Services\ExifService;

test('it returns empty array for non-image mime types', function () {
    $exifService = new ExifService();
    $content = 'not an image';
    
    $result = $exifService->extract($content, 'text/plain');
    
    expect($result)->toBe([]);
});

test('it returns empty array for images without EXIF', function () {
    $exifService = new ExifService();
    $pngContent = file_get_contents(__DIR__ . '/../fixtures/test.png');
    
    $result = $exifService->extract($pngContent, 'image/png');
    
    expect($result)->toBe([]);
});

test('it parses GPS DMS coordinates to decimal correctly', function () {
    $service = new ExifService();
    
    $method = new ReflectionMethod($service, 'getGpsCoordinate');
    $method->setAccessible(true);
    
    $gpsData = [
        'GPSLatitude' => [[37, 1], [46, 1], [2940, 100]],
        'GPSLatitudeRef' => 'N',
    ];
    
    $lat = $method->invoke($service, $gpsData, 'GPSLatitude', 'GPSLatitudeRef');
    
    expect($lat)->toBeFloat()
        ->and(round($lat, 4))->toBe(37.7748);
});

test('it applies negative sign for south and west coordinates', function () {
    $service = new ExifService();
    
    $method = new ReflectionMethod($service, 'getGpsCoordinate');
    $method->setAccessible(true);
    
    $gpsData = [
        'GPSLatitude' => [[33, 1], [51, 1], [54, 1]],
        'GPSLatitudeRef' => 'S',
    ];
    
    $lat = $method->invoke($service, $gpsData, 'GPSLatitude', 'GPSLatitudeRef');
    
    expect($lat)->toBeLessThan(0)
        ->and(round($lat, 4))->toBe(-33.865);
});

test('it parses string format GPS coordinates from Imagick', function () {
    $service = new ExifService();
    
    $method = new ReflectionMethod($service, 'getGpsCoordinate');
    $method->setAccessible(true);
    
    $gpsData = [
        'GPSLongitude' => '122/1, 25/1, 9/1',
        'GPSLongitudeRef' => 'W',
    ];
    
    $lng = $method->invoke($service, $gpsData, 'GPSLongitude', 'GPSLongitudeRef');
    
    expect($lng)->toBeLessThan(0)
        ->and(round($lng, 4))->toBe(-122.4192);
});

test('it parses rational numbers correctly', function () {
    $service = new ExifService();
    
    $method = new ReflectionMethod($service, 'parseRational');
    $method->setAccessible(true);
    
    expect($method->invoke($service, '3/2'))->toBe(1.5)
        ->and($method->invoke($service, [3, 2]))->toBe(1.5)
        ->and($method->invoke($service, 1.5))->toBe(1.5)
        ->and($method->invoke($service, '1.5'))->toBe(1.5);
});

test('it formats shutter speed correctly', function () {
    $service = new ExifService();
    
    $method = new ReflectionMethod($service, 'formatShutterSpeed');
    $method->setAccessible(true);
    
    expect($method->invoke($service, '1/250'))->toBe('1/250s')
        ->and($method->invoke($service, '1/1000'))->toBe('1/1000s')
        ->and($method->invoke($service, 2.5))->toBe('2.5s')
        ->and($method->invoke($service, [1, 125]))->toBe('1/125s');
});

test('it parses EXIF datetime to ISO 8601', function () {
    $service = new ExifService();
    
    $method = new ReflectionMethod($service, 'parseExifDateTime');
    $method->setAccessible(true);
    
    $result = $method->invoke($service, '2024:12:25 14:30:00');
    
    expect($result)->toContain('2024-12-25')
        ->and($result)->toContain('14:30:00');
});

test('it handles invalid datetime gracefully', function () {
    $service = new ExifService();
    
    $method = new ReflectionMethod($service, 'parseExifDateTime');
    $method->setAccessible(true);
    
    expect($method->invoke($service, '0000:00:00 00:00:00'))->toBeNull()
        ->and($method->invoke($service, ''))->toBeNull();
});

test('it extracts exposure program name', function () {
    $service = new ExifService();
    
    $method = new ReflectionMethod($service, 'getExposureProgramName');
    $method->setAccessible(true);
    
    expect($method->invoke($service, 1))->toBe('manual')
        ->and($method->invoke($service, 2))->toBe('auto')
        ->and($method->invoke($service, 3))->toBe('aperture priority')
        ->and($method->invoke($service, 4))->toBe('shutter priority');
});

test('it parses flash info from bitmask', function () {
    $service = new ExifService();
    
    $method = new ReflectionMethod($service, 'getFlashInfo');
    $method->setAccessible(true);
    
    $flashFired = $method->invoke($service, 0x01);
    expect($flashFired['fired'])->toBeTrue();
    
    $flashOff = $method->invoke($service, 0x10);
    expect($flashOff['fired'])->toBeFalse()
        ->and($flashOff['mode'])->toBe('off');
    
    $flashAuto = $method->invoke($service, 0x19);
    expect($flashAuto['fired'])->toBeTrue()
        ->and($flashAuto['mode'])->toBe('auto');
});
