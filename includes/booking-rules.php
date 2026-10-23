<?php

function hsEscape(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function hsValidBookingDates(string $start, string $end): bool
{
    $startDate = DateTimeImmutable::createFromFormat('!Y-m-d', $start);
    $endDate = DateTimeImmutable::createFromFormat('!Y-m-d', $end);

    return $startDate !== false
        && $endDate !== false
        && $startDate->format('Y-m-d') === $start
        && $endDate->format('Y-m-d') === $end
        && $startDate >= new DateTimeImmutable('today')
        && $endDate > $startDate;
}

function hsHasConflict(mysqli $conn, int $vehicleId, string $start, string $end): bool
{
    $stmt = $conn->prepare(
        "SELECT id
         FROM bookings
         WHERE vehicle_id = ?
           AND status IN ('pending', 'approved', 'confirmed')
           AND start_date <= ?
           AND end_date >= ?
         LIMIT 1"
    );
    $stmt->bind_param('iss', $vehicleId, $end, $start);
    $stmt->execute();

    return $stmt->get_result()->num_rows > 0;
}