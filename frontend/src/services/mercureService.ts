import { EventSourcePolyfill } from "event-source-polyfill";
import { useAuthStore } from "@/stores/authStore";

export class MercureService {
  private eventSource: EventSourcePolyfill | null = null;
  private reconnectTimeout: number | null = null;
  private authStore = useAuthStore();

  constructor(private hubUrl: string) {}

  async subscribe(topic: string, callback: (data: any) => void): Promise<void> {
    try {
      const token = await this.getMercureToken();

      const url = new URL(this.hubUrl);
      url.searchParams.append("topic", topic);

      this.eventSource = new EventSourcePolyfill(url.toString(), {
        withCredentials: true,
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });

      this.eventSource.onerror = (event: Event) => {
        console.error("EventSource failed:", event);
        const target = event.target as EventSourcePolyfill;
        console.error("ReadyState:", target.readyState);
        console.error("Status:", target.status);
        console.error("StatusText:", target.statusText);
        console.error("Response headers:", target.getAllResponseHeaders());
        if (target.readyState === EventSource.CLOSED) {
          console.error("Connection was closed");
          this.reconnect(topic, callback);
        }
      };

      this.eventSource.onmessage = (event: MessageEvent) => {
        callback(JSON.parse(event.data));
      };
    } catch (error) {
      console.error("Error in subscribe:", error);
    }
  }

  unsubscribe(): void {
    if (this.eventSource) {
      this.eventSource.close();
      this.eventSource = null;
    }
    if (this.reconnectTimeout) {
      clearTimeout(this.reconnectTimeout);
      this.reconnectTimeout = null;
    }
  }

  private async getMercureToken(): Promise<string> {
    try {
      if (!this.authStore.mercureToken) {
        await this.authStore.fetchMercureToken();
      }
      if (!this.authStore.mercureToken) {
        throw new Error("Failed to obtain Mercure token");
      }
      return this.authStore.mercureToken;
    } catch (error) {
      console.error("Error getting Mercure token:", error);
      throw error;
    }
  }

  private reconnect(topic: string, callback: (data: any) => void): void {
    if (!this.reconnectTimeout) {
      this.reconnectTimeout = window.setTimeout(() => {
        this.subscribe(topic, callback);
      }, 5000); // Try to reconnect after 5 seconds
    }
  }
}