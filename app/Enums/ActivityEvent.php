<?php

namespace App\Enums;

enum ActivityEvent: string
{
    // Orders
    case OrderStatusChanged = 'order.status_changed';
    case OrderCancelled = 'order.cancelled';
    case OrderDeleted = 'order.deleted';
    case OrderDiscountApplied = 'order.discount_applied';
    case OrderDiscountRemoved = 'order.discount_removed';
    case OrderDeliveryFeeUpdated = 'order.delivery_fee_updated';

    // KOT
    case KotItemDeleted = 'kot.item_deleted';
    case KotItemQuantityUpdated = 'kot.item_quantity_updated';
    case KotItemDeletedFromOrder = 'kot.item_deleted_from_order';

    // Payments
    case PaymentCreated = 'payment.created';
    case PaymentUpdated = 'payment.updated';
    case PaymentDeleted = 'payment.deleted';

    // Cash register
    case CashSessionOpened = 'cash.session_opened';
    case CashSessionClosed = 'cash.session_closed';
    case CashIn = 'cash.cash_in';
    case CashOut = 'cash.cash_out';
    case CashSafeDrop = 'cash.safe_drop';

    // Inventory
    case InventoryMovementCreated = 'inventory.movement_created';
    case PurchaseOrderCreated = 'inventory.po_created';
    case PurchaseOrderReceived = 'inventory.po_received';
    case PurchaseOrderAmountUpdated = 'inventory.po_amount_updated';
    case PurchaseOrderDeleted = 'inventory.po_deleted';
    case PurchasePaymentCreated = 'inventory.po_payment_created';
    case PurchasePaymentUpdated = 'inventory.po_payment_updated';
    case PurchasePaymentDeleted = 'inventory.po_payment_deleted';
    case PurchaseReturnPaymentCreated = 'inventory.purchase_return_payment_created';

    // Staff / settings
    case StaffCreated = 'staff.created';
    case StaffUpdated = 'staff.updated';
    case StaffDeleted = 'staff.deleted';
    case StaffRoleChanged = 'staff.role_changed';
    case RoleCreated = 'settings.role_created';
    case RoleUpdated = 'settings.role_updated';
    case RoleDeleted = 'settings.role_deleted';
    case RolePermissionGranted = 'settings.role_permission_granted';
    case RolePermissionRevoked = 'settings.role_permission_revoked';

    // Expenses
    case ExpenseCreated = 'expense.created';
    case ExpenseUpdated = 'expense.updated';
    case ExpenseDeleted = 'expense.deleted';

    // Table reservations (restaurant)
    case TableReservationCreated = 'reservation.created';
    case TableReservationUpdated = 'reservation.updated';
    case TableReservationStatusChanged = 'reservation.status_changed';
    case TableReservationDeleted = 'reservation.deleted';

    // Delivery executives
    case DeliveryExecutiveCreated = 'delivery_executive.created';
    case DeliveryExecutiveUpdated = 'delivery_executive.updated';
    case DeliveryExecutiveDeleted = 'delivery_executive.deleted';

    // Auth
    case UserLoggedIn = 'auth.login';
    case UserLoggedOut = 'auth.logout';

    // Folio
    case FolioChargeAdded = 'folio.charge_added';
    case FolioChargeUpdated = 'folio.charge_updated';
    case FolioChargeDeleted = 'folio.charge_deleted';
    case FolioSettled = 'folio.settled';
    case FolioOrderCharged = 'folio.order_charged';

    // Housekeeping
    case HousekeepingTaskCreated = 'housekeeping.task_created';
    case HousekeepingTaskUpdated = 'housekeeping.task_updated';
    case HousekeepingTaskStarted = 'housekeeping.task_started';
    case HousekeepingTaskCompleted = 'housekeeping.task_completed';

    // Hotel
    case ReservationCreated = 'hotel.reservation_created';
    case ReservationCancelled = 'hotel.reservation_cancelled';
    case GuestCheckedIn = 'hotel.guest_checked_in';
    case GuestCheckedOut = 'hotel.guest_checked_out';

    public function category(): string
    {
        return match (true) {
            str_starts_with($this->value, 'order.') => 'order',
            str_starts_with($this->value, 'kot.') => 'kot',
            str_starts_with($this->value, 'payment.') => 'payment',
            str_starts_with($this->value, 'cash.') => 'cash',
            str_starts_with($this->value, 'inventory.') => 'inventory',
            str_starts_with($this->value, 'staff.') => 'staff',
            str_starts_with($this->value, 'settings.') => 'settings',
            str_starts_with($this->value, 'expense.') => 'expense',
            str_starts_with($this->value, 'reservation.') => 'reservation',
            str_starts_with($this->value, 'delivery_executive.') => 'delivery',
            str_starts_with($this->value, 'auth.') => 'auth',
            str_starts_with($this->value, 'folio.') => 'folio',
            str_starts_with($this->value, 'housekeeping.') => 'housekeeping',
            str_starts_with($this->value, 'hotel.') => 'hotel',
            default => 'general',
        };
    }

    public function module(): string
    {
        return match (true) {
            str_starts_with($this->value, 'cash.') => 'cash_register',
            str_starts_with($this->value, 'inventory.') => 'inventory',
            str_starts_with($this->value, 'hotel.') => 'hotel',
            str_starts_with($this->value, 'folio.') => 'hotel',
            str_starts_with($this->value, 'housekeeping.') => 'hotel',
            default => 'core',
        };
    }

    public function label(): string
    {
        return str_replace(['.', '_'], ' ', $this->value);
    }
}
