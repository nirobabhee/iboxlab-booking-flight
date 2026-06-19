<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference')->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('flight_id');
            $table->string('provider');
            $table->string('carrier', 40);
            $table->string('flight_no', 40);
            $table->string('from_airport', 40);
            $table->string('to_airport', 40);
            $table->dateTime('depart_at');
            $table->dateTime('arrive_at');
            $table->unsignedTinyInteger('stops')->default(0);
            $table->unsignedTinyInteger('passengers')->default(1);
            $table->decimal('price_per_passenger', 28, 8);
            $table->decimal('total_price', 28, 8);
            $table->string('currency', 40)->default('USD');
            $table->json('provider_options')->nullable();
            $table->string('contact_name');
            $table->string('contact_email');
            $table->string('contact_phone', 40)->nullable();
            $table->unsignedTinyInteger('status')->default(1)->comment('Booking status: 1=>confirmed, 2=>cancelled, 3=>refunded');
            $table->timestamps();

            $table->index(['flight_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
