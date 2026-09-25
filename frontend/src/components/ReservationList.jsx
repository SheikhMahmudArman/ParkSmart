import { Link } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import { api, money } from "../api";
import {
  useData,
  useAction,
  ActionNotice,
  Notice,
  Panel,
  Table,
} from "./DataUI";

export default function ReservationList({ staff = false }) {
  const { user } = useAuth();
  const state = useData(
    staff ? "/reservations" : `/users/${user.id}/reservations`,
  );

  const action = useAction(state.reload);
  const rows = staff ? state.data || [] : state.data?.reservations || [];
  const columns = [
    { key: "id", label: "#" },
    { key: "lot_name", label: "Lot" },
    { key: "spot", label: "Space" },
    { key: "vehicle_plate", label: "Vehicle" },
    { key: "date", label: "Date" },
    { key: "start", label: "Start" },
    { key: "end", label: "End" },
    {
      key: "total_amount",
      label: "Booked price",
      render: (r) => money(r.total_amount),
    },
    { key: "status", label: "Status" },
    { key: "payment_status", label: "Payment" },
  ];

  return (
    <Panel title={staff ? "Reservations" : "My reservations"}>
      <p>
        Times use Asia/Dhaka. Billing rounds up to each started hour. Demo
        payments do not charge money. Paid bookings cannot be cancelled.
      </p>
      {!staff && (
        <Link className="btn btn-primary mb-3" to="/driver/search">
          Find parking
        </Link>
      )}
      <Notice {...state} />
      <ActionNotice {...action} />
      <Table
        rows={rows}
        columns={columns}
        actions={(r) => (
          <div className="d-flex gap-2 flex-wrap">
            {staff && r.status === "Pending" && (
              <button
                disabled={action.busy}
                className="btn btn-sm btn-primary"
                onClick={() =>
                  action.run(() =>
                    api(`/reservations/${r.id}`, {
                      method: "PUT",
                      body: { status: "Confirmed" },
                    }),
                  )
                }
              >
                Confirm
              </button>
            )}

            {!staff &&
              r.payment_status !== "Paid" &&
              !["Cancelled", "Expired"].includes(r.status) && (
                <button
                  disabled={action.busy}
                  className="btn btn-sm btn-success"
                  onClick={() =>
                    action.run(
                      () =>
                        api(`/reservations/${r.id}/pay`, {
                          method: "POST",
                          body: { payment_method: "Demo" },
                        }),
                      "Demo payment recorded. No money charged.",
                    )
                  }
                >
                  Pay demo
                </button>
              )}
              
            {["Pending", "Confirmed"].includes(r.status) &&
              r.payment_status !== "Paid" && (
                <button
                  disabled={action.busy}
                  className="btn btn-sm btn-outline-danger"
                  onClick={() => {
                    if (confirm("Cancel this reservation?"))
                      action.run(
                        () =>
                          api(
                            `/reservations/${r.id}`,
                            staff
                              ? { method: "PUT", body: { status: "Cancelled" } }
                              : { method: "DELETE" },
                          ),
                        "Reservation cancelled",
                      );
                  }}
                >
                  Cancel
                </button>
              )}
          </div>
        )}
      />
    </Panel>
  );
}
