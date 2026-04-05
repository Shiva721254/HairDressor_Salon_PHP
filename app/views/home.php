<?php
declare(strict_types=1);
/** @var ?array $user */
$user = $_SESSION['user'] ?? null;
$isLoggedIn = is_array($user);
?>

<div class="py-4 py-lg-5">
    <div class="row align-items-center g-4">

        <!-- LEFT: MAIN MESSAGE -->
        <div class="col-lg-7">
            <h1 class="display-5 fw-bold mb-3">Salon Appointment System</h1>

            <p class="lead text-muted mb-4">
                Book appointments with your preferred hairdresser, view your schedule, and manage bookings easily.
            </p>

            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-primary btn-lg" href="/appointments/new">Book an appointment</a>
                <a class="btn btn-outline-secondary btn-lg" href="/hairdressers">Browse hairdressers</a>
                <a class="btn btn-outline-secondary btn-lg" href="/services">View services</a>

                <?php if (!$isLoggedIn): ?>
                    <a class="btn btn-outline-primary btn-lg" href="/login">Login</a>
                <?php endif; ?>
            </div>

            <div class="mt-4 small text-muted">
                Tip: After logging in you can view and manage your appointments.
            </div>
        </div>

        <!-- RIGHT: QUICK INFO CARD -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h2 class="h5 fw-semibold mb-3">What you can do</h2>

                    <ul class="list-unstyled mb-0">
                        <li class="d-flex gap-2 mb-2">
                            <span class="text-success">✓</span>
                            <span>Pick a hairdresser and time slot</span>
                        </li>
                        <li class="d-flex gap-2 mb-2">
                            <span class="text-success">✓</span>
                            <span>Availability checks prevent double bookings</span>
                        </li>
                        <li class="d-flex gap-2 mb-2">
                            <span class="text-success">✓</span>
                            <span>Manage upcoming and past appointments</span>
                        </li>
                        <li class="d-flex gap-2">
                            <span class="text-success">✓</span>
                            <span>Admin can manage services, hairdressers and availability</span>
                        </li>
                    </ul>

                    <hr class="my-4">

                    <h3 class="h6 mb-3 fw-semibold">📅 Opening Times</h3>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 opening-times-table">
                            <tbody>
                            <?php foreach (($openingTimes ?? []) as $ot): ?>
                                <?php $hours = (string)($ot['hours'] ?? ''); ?>
                                <?php $isClosed = ($hours === 'Closed'); ?>
                                <tr style="cursor: pointer;">
                                    <th scope="row" class="w-40"><?= htmlspecialchars((string)($ot['day'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                                    <td class="d-flex justify-content-between align-items-center gap-2">
                                        <span class="fw-500"><?= htmlspecialchars($hours, ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="badge <?= $isClosed ? 'bg-danger' : 'bg-success' ?>" style="font-size: 0.75rem;">
                                            <?= $isClosed ? '🔴 Closed' : '🟢 Open' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between small text-muted">
                        <span>Status</span>
                        <span class="badge text-bg-success">Running</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
