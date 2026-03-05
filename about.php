<?php
define('ACCESS_ALLOWED', true);
include 'header.php';
?>

<div class="bg-base-200 min-h-screen py-20">
    <div class="max-w-4xl mx-auto px-4">
        <div class="text-center mb-16">
            <h1 class="text-5xl font-black mb-6">About Property Portfolio</h1>
            <p class="text-xl opacity-70 leading-relaxed">
                We bridge the gap between real estate agencies and property buyers through a unified, high-performance digital ecosystem.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <div class="bg-primary/10 w-16 h-16 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-rocket text-2xl text-primary"></i>
                    </div>
                    <h2 class="card-title text-2xl font-bold mb-4">Our Mission</h2>
                    <p class="text-base-content/70">
                        To empower property companies of all sizes with the tools they need to manage their listings, showcase their professional brand, and reach a global audience with zero friction.
                    </p>
                </div>
            </div>

            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <div class="bg-secondary/10 w-16 h-16 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-users text-2xl text-secondary"></i>
                    </div>
                    <h2 class="card-title text-2xl font-bold mb-4">For Agencies</h2>
                    <p class="text-base-content/70">
                        Stop managing properties in silos. Our multi-tenant architecture allows your agency to have its own dedicated dashboard, team management, and branded marketplace presence.
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-20 card bg-neutral text-neutral-content shadow-2xl">
            <div class="card-body p-12 text-center">
                <h2 class="text-3xl font-bold mb-6">Ready to scale your property business?</h2>
                <p class="mb-10 opacity-80 max-w-xl mx-auto">
                    Join dozens of agencies already using Property Portfolio to streamline their operations and showcase their listings to the world.
                </p>
                <div class="card-actions justify-center">
                    <a href="register.php" class="btn btn-primary btn-lg rounded-xl px-12">Get Started Now</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>