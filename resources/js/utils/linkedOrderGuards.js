import { showPosAlert } from "./posAlerts.js";

export const LINKED_ORDER_NEW_KOT_MESSAGE =
    "Use New KOT to add new items to this order.";

export const notifyLinkedOrderUseNewKot = () => {
    showPosAlert("info", LINKED_ORDER_NEW_KOT_MESSAGE, { timer: 4500 });
};

/**
 * @returns {boolean} true when the action was blocked
 */
export const blockLinkedOrderItemAdds = (isLinkedOrderMode) => {
    if (!isLinkedOrderMode) {
        return false;
    }

    notifyLinkedOrderUseNewKot();
    return true;
};
